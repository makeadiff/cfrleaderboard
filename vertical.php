<?php
require 'common.php';
include("../donutleaderboard/_city_filter.php");

$view_level = i($QUERY, 'view_level', 'national');
$timeframe 	= intval(i($QUERY, 'timeframe', '0'));
$view 		= i($QUERY, 'view', 'top');
$action 	= i($QUERY, 'action', '');
$vertical_id= i($QUERY, 'vertical_id', 0);

$QUERY['no_cache'] = 1;
if($view_level != 'vertical') $vertical_id = 0;

//Ignoring verticals that are not being used anymore
$verticals_to_hide = array(6,10,11,12,13,14,15,16,7);
$all_verticals = $sql_madapp->getById("SELECT id,name FROM Vertical WHERE id NOT IN ( " . implode(",", $verticals_to_hide) . ") ORDER BY name");

$all_view_levels = array('national' => "National", 'vertical' => "Vertical"); // , 'coach' => "Coach"
$all_timeframes = array('1' => 'Day', '7' => 'Week', '0' => 'Overall');

$checks = array('is_deleted' => 'users.is_deleted=0');
if($vertical_id and $view_level == 'vertical')	$checks['vertical_id'] = "G.vertical_id=$vertical_id";
if($timeframe) $checks['timeframe'] = "D.created_at > DATE_SUB(NOW(), INTERVAL $timeframe DAY)";
$user_checks = $checks;
unset($user_checks['timeframe']);

$top_data = array();
$bottom_data = array();
$top_title = '';
$bottom_title = '';

$array_template = array('title' => '', 'data' => array(), 'show_in' => array());
$all_levels = array('vertical' => $array_template, 'fellow' => $array_template);
$all_levels['vertical']['show_in']	= array('national');
$all_levels['fellow']['show_in']	= array('national', 'vertical');
$all_levels['participation']['show_in']	= array('vertical');

foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		if($key == 'fellow') $name = 'Fundraiser';
		elseif($key == 'vertical') $name = "Vertical";

		$title = 'Top ' . $name;

		if($vertical_id) {
			$vertical_name = $sql_madapp->getOne("SELECT name FROM Vertical WHERE id=$vertical_id");
			$title .= " in $vertical_name";
			$children_sponsored_title = " by " . $vertical_name;
		} else {
			$children_sponsored_title = "Nationally";
		}

		if($timeframe == '1')  $title .= " on " . date("jS M");
		elseif($timeframe == '7') $title .= " for last week(" . date("jS M", strtotime("last week")) . ")";

		$all_levels[$key]['title'] = $title;
		$all_levels[$key]['data'] = getData($key);
		$all_levels['children_sponsored_title'] = $children_sponsored_title;
	}
}

function getData($key, $get_user_count = false) {
	global $timeframe,$view_level,$vertical_id, $mem, $QUERY, $cache_expire, $checks, $sql, $user_checks, $year, $db_madapp, $verticals_to_hide;

	if(i($QUERY,'no_cache')) {
		$data = array();
	} else {
		return $mem->get("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key");
	}

	if($key == 'vertical') {
		if($vertical_id) $checks['vertical_id'] = "IQ.vid = $vertical_id";
		$values = getFromBothTables("IQ.vid AS id,IQ.vname AS name, COUNT(DISTINCT users.id,' ') as user_count_participated, %amount%", "
					users
					INNER JOIN
					(SELECT U.id AS uid,U.name as name,V.id AS vid,V.name AS vname
						FROM `$db_madapp`.User U
						INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
						INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
						INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
						WHERE U.status = 1 AND U.user_type = 'volunteer' AND UG.year = $year
						AND (G.type =  'strat' OR G.type =  'fellow')
						AND V.id NOT IN ( " . implode(",", $verticals_to_hide) . ")
						GROUP BY U.id,V.id) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "IQ.vid",'',$key);

		$data = array();
		foreach($values as $value) {
			$key = $value['id'];
			$data[$key]['id']=$key;
			$data[$key]['name']=$value['name'];
			$data[$key]['user_count_participated']=$value['user_count_participated'];
			$data[$key]['amount']=$value['amount'];
			$data[$key]['target_percentage'] = 0;
			$data[$key]['participation_percentage'] = 0;
			$data[$key]['user_count_total'] = 0;
		}

		// dump($data);

		$all_verticals = $sql->getById("SELECT id,name FROM `makeadiff_madapp`.Vertical WHERE id NOT IN ( " . implode(",", $verticals_to_hide) . ") ORDER BY name");
		// dump($all_verticals);

		foreach ($all_verticals as $this_vertical_id => $vertical_name) {
			if(!isset($data[$this_vertical_id]['amount'])) continue;
			$vertical_check = "AND V.id=$this_vertical_id";
			$data_user = $sql->getById("SELECT users.id, TRIM(CONCAT(users.first_name, ' ', users.last_name)) AS name
						FROM users
						INNER JOIN
						(SELECT U.id AS uid,U.name,V.id AS vid,V.name AS vname
							FROM `$db_madapp`.User U
							INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
							INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
							INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
							WHERE U.status = 1 AND U.user_type = 'volunteer' AND UG.year = $year $vertical_check
							AND (G.type =  'strat' OR G.type =  'fellow')
							AND V.id NOT IN ( " . implode(",", $verticals_to_hide) . ")
							GROUP BY U.id,V.id) IQ
						ON IQ.uid = users.madapp_user_id
						GROUP BY users.madapp_user_id");

			$data[$this_vertical_id]['user_count_total'] = count($data_user);

			$data[$this_vertical_id]['target_percentage'] = intval($data[$this_vertical_id]['amount'] / ($data[$this_vertical_id]['user_count_total'] * 12000) * 100);
			$data[$this_vertical_id]['participation_percentage'] = intval($data[$this_vertical_id]['user_count_participated'] / ($data[$this_vertical_id]['user_count_total']) * 100);
		}

		// dump($data);

		usort($data,"compare_participation");

	}elseif($key == 'fellow') {
		$vertical_check = '';
		if($vertical_id) {
			$checks['vertical_id'] = "IQ.vid = $vertical_id";
			$vertical_check = "AND V.id=$vertical_id";
		}
		$data = getFromBothTables("users.id, TRIM(CONCAT(users.first_name, ' ', users.last_name)) AS name, %amount%", "
					users
					INNER JOIN
					(SELECT U.id AS uid,U.name,V.id AS vid,V.name AS vname
						FROM `$db_madapp`.User U
						INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
						INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
						INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
						WHERE U.status = 1 AND U.user_type = 'volunteer' AND UG.year = $year $vertical_check
						AND (G.type =  'national' OR G.type =  'strat' OR G.type =  'fellow')
						AND V.id NOT IN ( " . implode(",", $verticals_to_hide) . ")
						GROUP BY U.id,V.id) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id","",$key);
	}

	$mem->set("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key", $data, $cache_expire);

	return $data;
}

// This is kind of horrible. But there was a lot of code repetation earlier - so I switched to this approch. Need as have to do the same check on two seperate tables.
function getFromBothTables($select, $tables, $group_by = '', $where = '',$key='') {
	global $top_count, $sql, $checks, $city_checks;

	$order_and_limits = ''; //"ORDER BY amount DESC\nLIMIT 0, " . ($top_count * 20);

	$filter = "WHERE $city_checks";
	if($checks) $filter .= " AND " . implode(" AND ", array_values($checks));

	if ($group_by == '') {
		$query = "SELECT $select FROM $tables $filter $where $order_and_limits";
	} else {
		$query = "SELECT $select FROM $tables $filter $where GROUP BY $group_by $order_and_limits";
	}

	$donut_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.donation_amount) AS amount, COALESCE(COUNT(DISTINCT D.donour_id),0) AS donor_count, GROUP_CONCAT(users.id,",") as user_ids, users.first_name as Uname', 'INNER JOIN donations D ON D.fundraiser_id=users.id'), $query);
	$donut_data = $sql->getById($donut_query);

	$extdon_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.amount) AS amount, COALESCE(COUNT(DISTINCT D.donor_id),0) AS donor_count, GROUP_CONCAT(users.id,",") as user_ids', 'INNER JOIN external_donations D ON D.fundraiser_id=users.id'), $query);
	$extdon_data = $sql->getById($extdon_query);

	$data = $donut_data;

	if($key=='fellow'){
		$volunteer_data = "SELECT users.id,users.id as ID,CONCAT(users.first_name, ' ', users.last_name) AS name
												FROM users
												INNER JOIN cities C ON users.city_id = C.id
												INNER JOIN
												(SELECT DISTINCT U.id as uid, U.name, V.id as vid
													FROM `makeadiff_madapp`.User U
													INNER JOIN `makeadiff_madapp`.UserGroup UG on U.id = UG.user_id
													INNER JOIN `makeadiff_madapp`.`Group` G on G.id = UG.group_id
													INNER JOIN `makeadiff_madapp`.`Vertical` V on G.vertical_id = V.id
													WHERE U.status = 1
													AND U.user_type = 'volunteer'
													AND G.type = 'fellow'
												) IQ
												ON IQ.uid = users.madapp_user_id
												WHERE ".implode(" AND ", $checks).
												" GROUP BY users.madapp_user_id";

		$volunteer_data = $sql->getById($volunteer_data);
		// var_dump ($volunteer_data);
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
	}else{
		$data = $donut_data;
		foreach ($extdon_data as $id => $value) {
			if(isset($data[$id])) {
				$data[$id]['amount'] += $extdon_data[$id]['amount'];
				$data[$id]['donor_count'] += $extdon_data[$id]['donor_count'];
				$data[$id]['user_ids'] .= $extdon_data[$id]['user_ids'];

				$ids = explode(',',$data[$id]['user_ids']);
				sort($ids);
				$ids = array_filter(array_unique($ids));
				// echo $data[$id]['name'];
				// dump($ids);
				$data[$id]['user_count_participated']=count($ids);
			}
			else $data[$id] = $extdon_data[$id];
		}
	}

	usort($data, function($a, $b) {
		if($a['amount'] < $b['amount']) return 1;
		if($a['amount'] > $b['amount']) return -1;
		return 0;
	});

	return $data;
}

function compare_participation($a,$b){
	if($a['participation_percentage']==$b['participation_percentage']){
		return 0;
	}
	return ($a['participation_percentage'] < $b['participation_percentage']) ? 1 : -1;
}



$html = new HTML;
render('vertical.php', false);
