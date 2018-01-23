<?php
require 'common.php';
include("../donutleaderboard/_city_filter.php");


$view_level = i($QUERY, 'view_level', 'national');
$timeframe = intval(i($QUERY, 'timeframe', '0'));
$view = i($QUERY, 'view', 'top');
$action = i($QUERY, 'action', '');
$state_id = i($QUERY, 'state_id', 0);
$city_id = i($QUERY, 'city_id', 0);
$group_id = i($QUERY, 'group_id', 0);

$QUERY['no_cache'] = 1;

if($view_level != 'group' and $view_level != 'city' and $view_level != 'region') $state_id = 0;
if($view_level != 'group' and $view_level != 'city') $city_id = 0;
if($view_level != 'group') $group_id = 0;
// if($view_level != 'national') $group_id = 0;

setlocale(LC_MONETARY, 'en_IN');
$mem = new Memcached();
// $mem = new Mem(); //If error, Change this (Mem) to Memcached
$mem->addServer("127.0.0.1", 11211);

$year = 2017;
$cache_expire = 60 * 60;
$top_count = 30;
$all_states = $sql->getById("SELECT id,name FROM states");
$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");
$all_view_levels = array('national' => "National", 'city' => "City", 'group' => "Center"); // , 'coach' => "Coach"
$all_timeframes = array('1' => 'Day', '7' => 'Week', '0' => 'Overall');

$checks = array('is_deleted' => 'users.is_deleted=0');
// if($state_id and $view_level == 'region')	$checks['state_id'] = "C.state_id=$state_id";
if($group_id and $view_level == 'group')	$checks['group_id'] = "manager.group_id=$group_id";
if($city_id  and $view_level == 'city')		$checks['city_id']  = "users.city_id=$city_id";
if($timeframe) $checks['timeframe'] = "D.created_at > DATE_SUB(NOW(), INTERVAL $timeframe DAY)";
$user_checks = $checks;
unset($user_checks['timeframe']);

$filter = "WHERE $city_checks";
if($checks) $filter .= " AND " . implode(" AND ", array_values($checks));

$top_data = array();
$bottom_data = array();
$top_title = '';
$bottom_title = '';

$array_template = array('title' => '', 'data' => array(), 'show_in' => array());
$all_levels = array('region' => $array_template, 'city' => $array_template, 'group' => $array_template, 'coach' => $array_template, 'user' => $array_template, 'volunteer' => $array_template,'fellow' => $array_template, 'nonparticipative' => $array_template );

$all_levels['city']['show_in']		= array('national', 'region');
$all_levels['group']['show_in']		= array('region', /*'city'*/);
$all_levels['coach']['show_in']		= array('national', 'region', 'city', 'group');
$all_levels['user']['show_in']		= array('national', 'region', 'city', 'group', 'coach');
$all_levels['fellow']['show_in']	= array('city');
$all_levels['volunteer']['show_in']	= array('city');
$all_levels['nonparticipative']['show_in']	= array('city');

// Get the totals for the city card.
$total_user_count = 0;
$total_donation = 0;
$total_volunteer_particpated = 0;
if(!$total_user_count or !$total_donation) {
	if($view_level == 'national' || $view_level == 'city') {
		$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count
			FROM users
			INNER JOIN cities C ON C.id=users.city_id
			INNER JOIN
				(SELECT U.id as uid, U.name
				FROM `makeadiff_madapp`.User U
				WHERE U.status = 1
				AND U.user_type = 'volunteer'
				) IQ
			ON IQ.uid = users.madapp_user_id
			WHERE " . implode(" AND ", $user_checks));

		$total_donation = $sql->getOne("SELECT SUM(D.donation_amount) AS sum
			FROM users
			INNER JOIN cities C ON C.id=users.city_id
			INNER JOIN donations D ON D.fundraiser_id=users.id
			$filter");
		$total_donation += $sql->getOne("SELECT SUM(D.amount) AS sum
			FROM users
			INNER JOIN cities C ON C.id=users.city_id
			INNER JOIN external_donations D ON D.fundraiser_id=users.id
			$filter");
		$user_data = getFromBothTables("users.id,%amount%,
					users.id",
					"users
						INNER JOIN cities ON users.city_id=cities.id
					%donation_table%",
					"users.id","",false);

			// var_dump($user_data);
			foreach ($user_data as $data) {
				if($data['amount']>0){
					$total_volunteer_particpated++;
				}
			}

	}
}

foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		$name = ucfirst($key);
		if($name == 'User') $name = 'Fundraiser';
		if($name == 'Group') $name = 'Center';


		$title = 'Top ' . $name;

		if($name == 'Fellow') $title = 'Fellow Participation';
		if($name == 'Volunteer') $title = 'Volunteer Participation';
		if($name == 'Nonparticipative') $title = '';

		if($group_id) {
			$city_name = $sql->getOne("SELECT name FROM cities WHERE id=$city_id");
			$title .= " in $city_name";
			$group_name = $sql->getOne("SELECT name FROM groups WHERE id=$group_id");
			$children_sponsored_title = " by " . $group_name;
			$children_count = $sql_madapp->getOne("SELECT COUNT(*) FROM Student
											INNER JOIN Center ON Center.id = Student.center_id
											WHERE Student.status = 1 AND Center.name = '$group_name'");
		} elseif($city_id) {
			$city_name = $sql->getOne("SELECT name FROM cities WHERE id=$city_id");
			$title .= " in $city_name";
			$children_sponsored_title = " by " . $city_name;
			$madapp_city_id = city_transilation_donut_to_madapp($city_id);
			$children_count = $sql_madapp->getOne("SELECT COUNT(*) FROM Student
											INNER JOIN Center ON Center.id = Student.center_id
											INNER JOIN City ON City.id = Center.city_id
											WHERE Student.status = 1 AND Center.status = 1 AND City.id = $madapp_city_id");
		} elseif($state_id) {
			$state_name = $sql->getOne("SELECT name FROM states WHERE id=$state_id");
			$title .= " in $state_name";
			$children_sponsored_title = " by " . $state_name;
			$madapp_region_id = state_transilation_donut_to_madapp($state_id);
			$children_count = $sql_madapp->getOne("SELECT COUNT(*) FROM Student
											INNER JOIN Center ON Center.id = Student.center_id
											INNER JOIN City ON City.id = Center.city_id
											-- INNER JOIN Region ON Region.id = City.region_id
											WHERE Student.status = 1 AND Center.status = 1 /*AND Region.id = $madapp_region_id*/");
		} else {
			$children_sponsored_title = "Nationally";
			$children_count = $sql_madapp->getOne("SELECT COUNT(*) FROM Student
											INNER JOIN Center
											ON Center.id = Student.center_id
											WHERE Student.status = 1 AND Center.status = 1");
		}

		if($timeframe == '1') {
			$title .= " on " . date("jS M");

		} elseif($timeframe == '7') {
			$title .= " for last week(" . date("jS M", strtotime("last week")) . ")";
		}

		$all_levels[$key]['title'] = $title;
		$all_levels[$key]['data'] = getData($key);
		$all_levels['children_sponsored_title'] = $children_sponsored_title;
		$all_levels['children_count'] = $children_count;
	}
}

$level_hirarchy = array_keys($all_view_levels);
$key_pos = array_search($view_level, $level_hirarchy);
$next_view_level = i($level_hirarchy, $key_pos + 1, 0);
$oxygen_card_data = array();
if($next_view_level) $oxygen_card_data = getData($next_view_level, true);

// Get the hirarchy
if(i($QUERY,'no_cache')) $menu = array();
else $menu = $mem->get("Infogen:index/menu");

if(!$menu) {
	foreach ($all_states as $this_state_id => $state_name) {
		$all_cities_in_state = $sql->getById("SELECT id, name FROM cities ORDER BY name");
		$menu[$this_state_id] = array('name' => $state_name, 'id' => $this_state_id, 'cities' => array());

		foreach ($all_cities_in_state as $this_city_id => $city_name) {
			$all_groups_in_city = $sql->getById("SELECT id, name FROM groups WHERE city_id=$this_city_id");
			$menu[$this_state_id]['cities'][$this_city_id] = array('name' => $city_name, 'id' => $this_city_id, 'groups' => array());

			foreach ($all_groups_in_city as $this_group_id => $group_name) {
				$all_users_in_group = $sql->getById("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM users WHERE group_id=$this_group_id");
				$menu[$this_state_id]['cities'][$this_city_id]['groups'][$this_group_id] = array('name' => $group_name, 'id' => $this_group_id, 'users' => $all_users_in_group);
			}
		}
	}
	$mem->set("Infogen:index/menu", $menu, $cache_expire) or die("Couldn't cache data.");
}

function getData($key, $get_user_count = false) {
	global $timeframe,$view_level,$state_id,$city_id,$group_id, $mem, $QUERY, $cache_expire, $checks, $sql, $user_checks, $total_donation, $total_user_count, $all_cities;

	if(i($QUERY,'no_cache')) {
		$data = array();
	} else {
		return $mem->get("Infogen:index/data#$timeframe,$view_level,$state_id,$city_id,$group_id,$key");
	}

	if($key == 'region') {
		$data = getFromBothTables("S.id,S.name, %amount%", "states S
					INNER JOIN cities C ON C.state_id=S.id
					INNER JOIN users ON users.city_id=C.id
					%donation_table%", "S.id");

		//To get the number of users who have donated above Rs. 12,000
		$user_data = getFromBothTables("users.id,%amount%, S.id as state_id", "states S
					INNER JOIN cities C ON C.state_id=S.id
					INNER JOIN users ON users.city_id=C.id
					%donation_table%", "users.id","",false);

		foreach($data as $key => $row) {
			$data[$key]['user_count_participated'] = 0;
		}

		foreach($user_data as $row) {
			if($row['amount'] > 0) {
				$data[$row['state_id']]['user_count_participated'] ++;
			}
		}

		$user_count_data = $sql->getById("SELECT S.id, COUNT(users.id) AS count
				FROM users
				INNER JOIN cities C ON C.id=users.city_id
				INNER JOIN states S ON S.id=C.state_id
				WHERE " . implode(" AND ", $user_checks)
				. " GROUP BY S.id");

		foreach ($data as $key => $row) {
			$data[$key]['user_count'] = $user_count_data[$row['id']];
		}

	} elseif($key == 'city') {

		$data = getFromBothTables("C.id,C.name, %amount%",
					"cities C
						INNER JOIN users ON city_id=C.id
						INNER JOIN
						 (SELECT U.id as uid, U.name
						 	FROM `makeadiff_madapp`.User U
						 	WHERE U.status = 1
						 	AND U.user_type = 'volunteer'
						 ) IQ
						 ON IQ.uid = users.madapp_user_id
						%donation_table%",
					"C.id",
					'AND C.id != 25'); // 25 is Leadership Team

			// INNER JOIN
			// (SELECT U.id as uid, U.name
			// 	FROM `makeadiff_madapp`.User U
			// 	WHERE U.status = 1
			// 	AND U.user_type = 'volunteer'
			// ) IQ
			// ON IQ.uid = users.madapp_user_id

		//To get the number of users who have donated above Rs. 0
		$user_data = getFromBothTables("DISTINCT users.id,%amount%,
					C.id as city_id",
					"cities C
						INNER JOIN `makeadiff_cfrapp`.users ON users.city_id=C.id
						INNER JOIN
						 (SELECT U.id as uid, U.name
						 	FROM `makeadiff_madapp`.User U
						 	WHERE U.status = 1
						 	AND U.user_type = 'volunteer'
						 ) IQ
						 ON IQ.uid = users.madapp_user_id
					%donation_table%",
					"users.id","",false);

		// dump($user_data);

		foreach($data as $key => $row) {
			$data[$key]['user_count_participated'] = 0;
			$data[$key]['target_percentage'] = 0;
			$data[$key]['participation_percentage'] = 0;
			$data[$key]['donor_count'] = 0;
		}

		foreach($user_data as $row) {
			$this_city_id = $row['city_id'];

			if($row['amount'] > 0 && !empty($data[$this_city_id]) and isset($data[$this_city_id]['user_count_participated'])) {
				$data[$this_city_id]['user_count_participated'] ++;
			}
		}

		foreach ($all_cities as $this_city_id => $city_name) {
			if(!isset($data[$this_city_id]['amount'])) continue;

			$data[$this_city_id]['user_count_total'] = $sql->getOne("
								SELECT DISTINCT COUNT(User.id)
								FROM `makeadiff_madapp`.User
								INNER JOIN `makeadiff_madapp`.City on City.id = User.city_id
								INNER JOIN
								(SELECT DISTINCT C.id as cid,C.madapp_city_id as mcid
									FROM cities C
								) IQ
								ON IQ.mcid = User.city_id
								WHERE status=1
								AND user_type = 'volunteer'
								AND IQ.cid=$this_city_id");

			$data[$this_city_id]['target_percentage'] = intval($data[$this_city_id]['amount'] / ($data[$this_city_id]['user_count_total'] * 12000) * 100);
			$data[$this_city_id]['participation_percentage'] = floatval($data[$this_city_id]['user_count_participated'] / ($data[$this_city_id]['user_count_total']) * 100);
		}


		$user_count_data = $sql->getById("SELECT C.id, COUNT(users.id) AS count
				FROM users
				INNER JOIN cities C ON C.id=users.city_id
				WHERE " . implode(" AND ", $user_checks)
				. " GROUP BY C.id");

		foreach ($data as $key => $row) {
			if(isset($row['id']))
				$data[$key]['user_count'] = $user_count_data[$row['id']];
		}

		usort($data,"compare_participation");

		// dump($data);

	} elseif($key == 'group') {

		$data = getFromBothTables("G.id,G.name AS name, %amount%", "users
					INNER JOIN groups G ON G.id=users.group_id
					INNER JOIN cities C ON C.id=users.city_id
					%donation_table%", "G.id", "AND G.type='center'");

		$user_data = getFromBothTables("users.id, %amount%, G.id as group_id", "users
					INNER JOIN groups G ON G.id=users.group_id
					INNER JOIN cities C ON C.id=users.city_id
					%donation_table%", "G.id", "AND G.name != 'Events'",false);

		foreach($data as $key => $row) {
			$data[$key]['user_count_participated'] = 0;
		}

		foreach($user_data as $row) {
			if($row['amount'] > 0 && !empty($data[$row['group_id']])) {
				$data[$row['group_id']]['user_count_participated'] ++;
			}
		}

		$user_count_data = $sql->getById("SELECT G.id, COUNT(users.id) AS count
			FROM users
			INNER JOIN user_role_maps RM ON RM.user_id=users.id
			INNER JOIN roles R ON R.id=RM.role_id
			INNER JOIN groups G ON G.id=users.group_id
			INNER JOIN cities C ON C.id=users.city_id
			WHERE R.id=9 AND " . implode(" AND ", $user_checks)
			. " GROUP BY G.id");

		foreach ($data as $key => $row) {
			if(!empty($user_count_data[$row['id']])) $data[$key]['user_count'] = $user_count_data[$row['id']];
			else  $data[$key]['user_count'] = 0;
		}
	} elseif($key == 'user') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users
					INNER JOIN cities C ON users.city_id=C.id
					INNER JOIN
					(SELECT DISTINCT U.id as uid, U.name
						FROM `makeadiff_madapp`.User U
						WHERE U.status = 1
						AND U.user_type = 'volunteer'
					) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id",'',true,$key);

	} elseif($key == 'fellow') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users
					INNER JOIN cities C ON users.city_id=C.id
					INNER JOIN
					(SELECT DISTINCT U.id as uid, U.name
						FROM `makeadiff_madapp`.User U
						INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
						INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
						WHERE U.status = 1
						AND U.user_type = 'volunteer'
						AND G.type = 'fellow'
						AND UG.year = 2017
					) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id",'','',$key);

	} elseif($key == 'volunteer') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users
					INNER JOIN cities C ON users.city_id=C.id
					INNER JOIN
					(SELECT DISTINCT U.id as uid, U.name
						FROM `makeadiff_madapp`.User U
						INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
						INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
						WHERE U.status = 1
						AND U.user_type = 'volunteer'
						AND G.type = 'volunteer'
					) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id",'','',$key);

	} elseif($key == 'nonparticipative') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users
					INNER JOIN cities C ON users.city_id=C.id
					INNER JOIN
					(SELECT DISTINCT U.id as uid, U.name
						FROM `makeadiff_madapp`.User U
						INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
						INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
						WHERE U.status = 1
						AND U.user_type = 'volunteer'
					) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id",'','',$key);

	}

	if($key=='volunteer' || $key=='fellow'){
		$partcipated_count = 0;
		$total_count = 0;
		$id = 0;

		foreach ($data as $value) {
			if($total_count == 0){
				$id = $value['id'];
			}
			$total_count++;
			if($value['amount']>0){
				$partcipated_count++;
			}
			else{
				unset($data[$value['id']]);
			}
		}

		$data[$id]['partcipated_count'] = $partcipated_count;
		$data[$id]['total_count'] = $total_count;

		if($total_count!=0)
			$data[$id]['participation_percentage'] = ($partcipated_count/$total_count)*100;
		else
			$data[$id]['participation_percentage'] = 0;

	} else if($key=='nonparticipative'){
		$nonpartcipated_count = 0;
		$total_count = 0;
		$id = 0;

		foreach ($data as $value) {
			$total_count++;
			if($value['amount']==0){
				$nonpartcipated_count++;
			}
			else{
				unset($data[$value['id']]);
			}
			$id = $value['id'];
		}

		$data[$id]['partcipated_count'] = $nonpartcipated_count;
		$data[$id]['total_count'] = $total_count;

		if($total_count!=0)
			$data[$id]['participation_percentage'] = ($nonpartcipated_count/$total_count)*100;
		else
			$data[$id]['participation_percentage'] = 0;

	}

	// dump($data);

	$mem->set("Infogen:index/data#$timeframe,$view_level,$state_id,$city_id,$group_id,$key", $data, $cache_expire);

 	return $data;
}

function getFromBothTables($select, $tables, $group_by, $where = '',$set_order_and_limits = true,$key='') {
	global $filter, $top_count, $sql, $checks;

	if($set_order_and_limits == true) {
		$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, " . ($top_count * 20);
	}else {
		$order_and_limits = "";
	}

	$query = "SELECT $select FROM $tables $filter $where GROUP BY $group_by $order_and_limits";
	$donut_query = str_replace(array('%amount%', '%donation_table%'), array('COALESCE(SUM(D.donation_amount),0) AS amount, COALESCE(COUNT(DISTINCT D.donour_id),0) AS donor_count', 'LEFT OUTER JOIN donations D ON D.fundraiser_id=users.id'), $query);
	$donut_data = $sql->getById($donut_query);

	$extdon_query = str_replace(array('%amount%', '%donation_table%'), array('COALESCE(SUM(D.amount),0) AS amount, COALESCE(COUNT(DISTINCT D.donor_id),0) as donor_count', 'LEFT OUTER JOIN external_donations D ON D.fundraiser_id=users.id'), $query);
	$extdon_data = $sql->getById($extdon_query);



	if($key == 'volunteer' || $key=='fellow' || $key == 'nonparticipative'){ //Getting All Volunteer Data
		switch ($key) {
			case 'nonparticipative':
				$volunteer_data = "SELECT users.id,users.id as ID,CONCAT(users.first_name, ' ', users.last_name) AS name
														FROM users
														INNER JOIN cities C ON users.city_id = C.id
														INNER JOIN
														(SELECT DISTINCT U.id as uid, U.name
															FROM `makeadiff_madapp`.User U
															INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
															INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
															WHERE U.status = 1
															AND U.user_type = 'volunteer'
														) IQ
														ON IQ.uid = users.madapp_user_id
														WHERE ".implode(" AND ", $checks).
														" GROUP BY users.madapp_user_id";
				break;
			case 'volunteer':
				$volunteer_data = "SELECT users.id,users.id as ID,CONCAT(users.first_name, ' ', users.last_name) AS name
												  	FROM users
											  	 	INNER JOIN cities C ON users.city_id = C.id
													 	INNER JOIN
								 						(SELECT DISTINCT U.id as uid, U.name
								 							FROM `makeadiff_madapp`.User U
								 							INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
								 							INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
								 							WHERE U.status = 1
								 							AND U.user_type = 'volunteer'
								 							AND G.type = 'volunteer'
								 						) IQ
														ON IQ.uid = users.madapp_user_id
														WHERE ".implode(" AND ", $checks).
														" GROUP BY users.madapp_user_id";
				break;
			case 'fellow':
				$volunteer_data = "SELECT users.id,users.id as ID,CONCAT(users.first_name, ' ', users.last_name) AS name
														FROM users
														INNER JOIN cities C ON users.city_id = C.id
														INNER JOIN
														(SELECT DISTINCT U.id as uid, U.name
															FROM `makeadiff_madapp`.User U
															INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
															INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
															WHERE U.status = 1
															AND U.user_type = 'volunteer'
															AND G.type = 'fellow'
														) IQ
														ON IQ.uid = users.madapp_user_id
														WHERE ".implode(" AND ", $checks).
														" GROUP BY users.madapp_user_id";
				break;
			default:
				$volunteer_data = "SELECT users.id,users.id as ID,CONCAT(users.first_name, ' ', users.last_name) AS name
												 	FROM users
												 	INNER JOIN cities C ON users.city_id = C.id
												  WHERE ".implode(" AND ", $checks).
													" GROUP BY users.madapp_user_id";
				break;
		}

		$volunteer_data = $sql->getById($volunteer_data);


		$data = array();

		foreach ($volunteer_data as $user_data) {
			$data[$user_data['id']]['id']=$user_data['id'];
			$data[$user_data['id']]['amount']=0;
			$data[$user_data['id']]['name']=$user_data['name'];
			$data[$user_data['id']]['donor_count']=0;
			$data[$user_data['id']]['partcipated_count']=0;
			$data[$user_data['id']]['total_count']=0;
			$data[$user_data['id']]['participation_percentage']=0;
		}

		foreach ($extdon_data as $id => $value) {
			if(isset($data[$id])){
				$data[$id]['amount'] += $extdon_data[$id]['amount'];
				$data[$id]['donor_count'] += $extdon_data[$id]['donor_count'];
			}
			else $data[$id]= $extdon_data[$id];
		}

		foreach ($donut_data as $id => $value) {
			if(isset($data[$id])){
				$data[$id]['amount'] += $donut_data[$id]['amount'];
				$data[$id]['donor_count'] += $donut_data[$id]['donor_count'];
			}
			else $data[$id]= $donut_data[$id];
		}
	}
	else{
		$data = $donut_data;
		foreach ($extdon_data as $id => $value) {
			if(isset($data[$id])){
				$data[$id]['amount'] += $extdon_data[$id]['amount'];
				$data[$id]['donor_count'] += $extdon_data[$id]['donor_count'];
			}
			else $data[$id]= $extdon_data[$id];
		}
	}


	// var_dump($data);

	uasort($data, function($a, $b) {
		if($a['amount'] < $b['amount']) return 1;
		if($a['amount'] > $b['amount']) return -1;
		return 0;
	});



	return $data;
	// }
}

function compare_participation($a,$b){
	if($a['participation_percentage']==$b['participation_percentage']){
		return 0;
	}
	return ($a['participation_percentage'] < $b['participation_percentage']) ? 1 : -1;
}

$html = new HTML;
render('index.php', false);

/*function money_format($format,$amount){
		return '<i class="fa fa-inr"></i>'.$amount;
}*/
