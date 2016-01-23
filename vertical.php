<?php
require 'common.php';
include("../donutleaderboard/_city_filter.php");

if($_SERVER['HTTP_HOST'] == 'makeadiff.in') {
	$sql_madapp= new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], "makeadiff_madapp");
} else {
	$sql_madapp= new Sql("makeadiff_madapp");
}

$view_level = i($QUERY, 'view_level', 'national');
$timeframe = intval(i($QUERY, 'timeframe', '0'));
$view = i($QUERY, 'view', 'top');
$action = i($QUERY, 'action', '');
$vertical_id = i($QUERY, 'vertical_id', 0);


$QUERY['no_cache'] = 1;

if($view_level != 'vertical') $vertical_id = 0;


setlocale(LC_MONETARY, 'en_IN');
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$year = 2015;
$cache_expire = 60 * 60;
$top_count = 8;
$all_verticals = $sql_madapp->getById("SELECT id,name FROM Vertical");
$all_view_levels = array('national' => "National", 'vertical' => "Vertical"); // , 'coach' => "Coach"
$all_timeframes = array('1' => 'Day', '7' => 'Week', '0' => 'Overall');

$checks = array('is_deleted' => 'users.is_deleted=0');
if($vertical_id and $view_level == 'vertical')	$checks['vertical_id'] = "G.vertical_id=$vertical_id";

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
$all_levels = array('vertical' => $array_template, 'nt' => $array_template, 'fellow' => $array_template, 'coach' => $array_template, 'volunteer' => $array_template);
$all_levels['vertical']['show_in']	= array('national');
$all_levels['nt']['show_in']		= array('national', 'vertical');
$all_levels['fellow']['show_in']	= array('national', 'vertical');
$all_levels['coach']['show_in']		= array('national', 'vertical');
$all_levels['volunteer']['show_in']	= array('national', 'vertical');

foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		$name = ucfirst($key);
		if($name == 'Nt') $name = 'National Team';

		$title = 'Top ' . $name;


		if($vertical_id) {
			$vertical_name = $sql_madapp->getOne("SELECT name FROM Vertical WHERE id=$vertical_id");
			$title .= " in $vertical_name";
			$children_sponsored_title = " by " . $vertical_name;
			$children_count = $sql_madapp->getOne("SELECT COUNT(*) FROM Student
											INNER JOIN Center
											ON Center.id = Student.center_id
											WHERE Student.status = 1 AND Center.name = '$group_name'");
		}else {
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
		$all_levels[$key]['area'] =
		$all_levels[$key]['data'] = getData($key);
		$all_levels['children_sponsored_title'] = $children_sponsored_title;
		$all_levels['children_count'] = $children_count;
	}
}

/*$level_hirarchy = array_keys($all_view_levels);
$key_pos = array_search($view_level, $level_hirarchy);
$next_view_level = i($level_hirarchy, $key_pos + 1, 0);
$oxygen_card_data = array();
if($next_view_level) $oxygen_card_data = getData($next_view_level, true);

//Get data for the Oxygen graphic
if(i($QUERY,'no_cache')) {
	$total_user_count = 0;
	$total_donation = 0;
} else {
	$total_user_count = $mem->get("Infogen:index/total_user_count#$timeframe,$view_level,$state_id,$city_id,$group_id");
	$total_donation = $mem->get("Infogen:index/total_donation#$timeframe,$view_level,$state_id,$city_id,$group_id");
}
if(!$total_user_count or !$total_donation) {
	if($view_level == 'national') {
		$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count 
			FROM users 
			INNER JOIN cities C ON C.id=users.city_id
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

	} elseif($view_level == 'region') {
		$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count 
			FROM users 
			INNER JOIN cities C ON C.id=users.city_id
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

	} elseif($view_level == 'city') {
		$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count 
			FROM users 
			WHERE " . implode(" AND ", $user_checks));
		$total_donation = $sql->getOne("SELECT SUM(D.donation_amount) AS sum
			FROM users 
			INNER JOIN donations D ON D.fundraiser_id=users.id
			$filter");
		$total_donation += $sql->getOne("SELECT SUM(D.amount) AS sum
			FROM users 
			INNER JOIN external_donations D ON D.fundraiser_id=users.id
			$filter");

	} elseif($view_level == 'group') {
		$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count 
			FROM users 
			INNER JOIN reports_tos RT ON RT.user_id=users.id
			INNER JOIN users manager ON manager.id=RT.manager_id
			WHERE " . implode(" AND ", $user_checks));
		$total_donation = $sql->getOne("SELECT SUM(D.donation_amount) AS sum
			FROM users 
			INNER JOIN reports_tos RT ON RT.user_id=users.id
			INNER JOIN users manager ON manager.id=RT.manager_id
			INNER JOIN donations D ON D.fundraiser_id=users.id
			$filter");
		$total_donation += $sql->getOne("SELECT SUM(D.amount) AS sum
			FROM users 
			INNER JOIN reports_tos RT ON RT.user_id=users.id
			INNER JOIN users manager ON manager.id=RT.manager_id
			INNER JOIN external_donations D ON D.fundraiser_id=users.id
			$filter");
	}

	$mem->set("Infogen:index/total_user_count#$timeframe,$view_level,$state_id,$city_id,$group_id", $total_user_count, $cache_expire);
	$mem->set("Infogen:index/total_donation#$timeframe,$view_level,$state_id,$city_id,$group_id", $total_donation, $cache_expire);
}
$target_amount = (($total_user_count * 70 / 100) * 12000) + (floor($total_user_count * 5 / 100) * 100000);
$remaining_amount = $target_amount - $total_donation;
$percentage_done = 0;
if($target_amount) $percentage_done = round($total_donation / $target_amount * 100, 2);
$ecs_count_remaining = ceil($remaining_amount / 6000);*/


function getData($key, $get_user_count = false) {
	global $timeframe,$view_level,$vertical_id, $mem, $QUERY, $cache_expire, $checks, $sql, $user_checks;

	if(i($QUERY,'no_cache')) {
		$data = array();
	} else {
		return $mem->get("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key");
	}

	if($key == 'vertical') {
		$data = getFromBothTables("V.id,V.name, %amount%", "makeadiff_madapp.Vertical V
					INNER JOIN makeadiff_madapp.`Group` G ON G.vertical_id=V.id
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.group_id = G.id
					INNER JOIN users ON users.madapp_user_id = UG.user_id
					%donation_table%", "V.id");

		if($get_user_count) {
			$user_count_data = $sql->getById("SELECT V.id, COUNT(users.id) AS count
					FROM users
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN makeadiff_madapp.Group G ON G.id = UG.group_id
					INNER JOIN makeadiff_madapp.Vertical ON V.id = G.vertical_id
					WHERE " . implode(" AND ", $user_checks)
					. " GROUP BY V.id");

			foreach ($data as $key => $row) {
				$data[$key]['user_count'] = $user_count_data[$row['id']];
			}
		}
	} elseif($key == 'nt') {


		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN makeadiff_madapp.Group G on G.id = UG.group_id
					INNER JOIN makeadiff_madapp.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type ='national' OR G.type = 'strat') AND R.id=9");




	} elseif($key == 'fellow') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN makeadiff_madapp.Group G on G.id = UG.group_id
					INNER JOIN makeadiff_madapp.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type = 'fellow') AND R.id=9");


	} elseif($key == 'coach') {

		$data = getFromBothTables("manager.id,CONCAT(manager.first_name, ' ', manager.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN makeadiff_madapp.Group G on G.id = UG.group_id
					INNER JOIN makeadiff_madapp.Vertical V on V.id = G.vertical_id
					%donation_table%", "manager.id","AND (G.type = 'fellow') AND R.id=9");



	} elseif($key == 'volunteer') {

		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN makeadiff_madapp.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN makeadiff_madapp.Group G on G.id = UG.group_id
					INNER JOIN makeadiff_madapp.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type = 'fellow') AND R.id=9");



	}

	$mem->set("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key", $data, $cache_expire);

	return $data;
}

function getFromBothTables($select, $tables, $group_by, $where = '') {
	global $filter, $top_count, $sql, $checks;
	
	$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, " . ($top_count * 20);

	$query = "SELECT $select FROM $tables $filter $where GROUP BY $group_by $order_and_limits";
	$donut_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.donation_amount) AS amount', 'INNER JOIN donations D ON D.fundraiser_id=users.id'), $query);
	$donut_data = $sql->getById($donut_query);

	$extdon_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.amount) AS amount', 'INNER JOIN external_donations D ON D.fundraiser_id=users.id'), $query);
	$extdon_data = $sql->getById($extdon_query);

	$data = $donut_data;




	foreach ($extdon_data as $id => $value) {
		if(isset($data[$id])) $data[$id]['amount'] += $extdon_data[$id]['amount'];
		else $data[$id]= $extdon_data[$id];
	}

	usort($data, function($a, $b) {
		if($a['amount'] < $b['amount']) return 1;
		if($a['amount'] > $b['amount']) return -1;
		return 0;
	});

	return array_slice($data, 0, 8);
}

$html = new HTML;
render('vertical.php', false);

/*function money_format($format,$amount){
		return '<i class="fa fa-inr"></i>'.$amount;
}*/

