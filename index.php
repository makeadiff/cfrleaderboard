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

if($view_level != 'group' and $view_level != 'city' and $view_level != 'region') $state_id = 0;
if($view_level != 'group' and $view_level != 'city') $city_id = 0;
if($view_level != 'group') $group_id = 0;

setlocale(LC_MONETARY, 'en_IN');
$year = 2015;
$top_count = 8;
$all_states = $sql->getById("SELECT id,name FROM states");
$all_cities = $sql->getById("SELECT id,name FROM cities");
$all_view_levels = array('national' => "National", 'region' => "Region", 'city' => "City", 'group' => "Group"); // , 'coach' => "Coach"
$all_timeframes = array('1' => 'Day', '7' => 'Week', '0' => 'Year');

$checks = array('users.is_deleted=0');
if($state_id and $view_level == 'region')	$checks[] = "C.state_id=$state_id";
if($group_id and $view_level == 'group')	$checks[] = "manager.group_id=$group_id";
if($city_id  and $view_level == 'city')		$checks[] = "users.city_id=$city_id";
if($timeframe) $checks[] = "D.created_at > DATE_SUB(NOW(), INTERVAL $timeframe DAY)";

$filter = "WHERE $city_checks";
if($checks) $filter .= " AND " . implode(" AND ", $checks);

$top_data = array();
$bottom_data = array();
$top_title = '';
$bottom_title = '';

$array_template = array('title' => '', 'data' => array(), 'show_in' => array());
$all_levels = array('region' => $array_template, 'city' => $array_template, 'group' => $array_template, 'coach' => $array_template, 'user' => $array_template);
$all_levels['region']['show_in']	= array('national');
$all_levels['city']['show_in']		= array('national', 'region');
$all_levels['group']['show_in']		= array('national', 'region', 'city');
$all_levels['coach']['show_in']		= array('national', 'region', 'city', 'group');
$all_levels['user']['show_in']		= array('national', 'region', 'city', 'group', 'coach');


foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		$all_levels[$key]['title'] = 'Top ' . ucfirst($key);// . ' in ' . 
		$all_levels[$key]['data'] = getData($key);
	}
}

// Get data for the Oxygen graphic
if($view_level == 'region') {
	$total_user_count = $sql->getOne("SELECT COUNT(users.id) AS count 
		FROM users 
		INNER JOIN cities C ON C.id=users.city_id
		WHERE " . implode(" AND ", $checks));
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
		WHERE " . implode(" AND ", $checks));
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
		WHERE " . implode(" AND ", $checks));
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
$target_amount = (($total_user_count * 70 / 100) * 12000) + (floor($total_user_count * 5 / 100) * 100000);
$remaining_amount = $target_amount - $total_donation;
$percentage_done = round($total_donation / $target_amount * 100, 2);
$ecs_count_remaining = ceil($remaining_amount / 6000);
// dump($target_amount, $remaining_amount, $percentage_done, $total_donation); exit;

// Get the hirarchy
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);
$menu = $mem->get("Infogen:index/menu");
if(!$menu) {
	foreach ($all_states as $state_id => $state_name) {
		$all_cities_in_state = $sql->getById("SELECT id, name FROM cities WHERE state_id=$state_id");
		$menu[$state_id] = array('name' => $state_name, 'id' => $state_id, 'cities' => $all_cities_in_state);

		foreach ($all_cities_in_state as $city_id => $city_name) {
			$all_groups_in_city = $sql->getById("SELECT id, name FROM groups WHERE city_id=$city_id");
			$menu[$state_id]['cities'][$city_id] = array('name' => $city_name, 'id' => $city_id, 'groups' => array());

			foreach ($all_groups_in_city as $group_id => $group_name) {
				$all_users_in_group = $sql->getById("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM users WHERE group_id=$group_id");
				$menu[$state_id]['cities'][$city_id]['groups'][$group_id] = array('name' => $group_name, 'id' => $group_id, 'users' => $all_users_in_group);
			}
		}
	}
	$mem->set("Infogen:index/menu", $menu) or die("Couldn't cache data.");
}

function getData($key) {
	$data = array();

	if($key == 'region') {
		$data = getFromBothTables("S.id,S.name, %amount%", "states S
					INNER JOIN cities C ON C.state_id=S.id
					INNER JOIN users ON users.city_id=C.id
					%donation_table%", "S.id");

	} elseif($key == 'city') {
		$data = getFromBothTables("C.id,C.name, %amount%", "cities C
					INNER JOIN users ON city_id=C.id
					%donation_table%", "C.id");

	} elseif($key == 'group') {
		$data = getFromBothTables("G.id,G.name, %amount%", "users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users manager ON manager.id=RT.manager_id
					INNER JOIN groups G ON G.id=manager.group_id
					INNER JOIN cities C ON C.id=users.city_id
					%donation_table%", "G.id");

	} elseif($key == 'coach') {
		$data = getFromBothTables("coach.id,CONCAT(coach.first_name, ' ', coach.last_name) AS name, %amount%", "users
					%donation_table%
					INNER JOIN reports_tos RT ON RT.user_id=users.id 
					INNER JOIN users AS coach ON RT.manager_id=coach.id
					INNER JOIN cities C ON C.id=users.city_id", "coach.id");

	} elseif($key == 'user') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users 
					INNER JOIN cities C ON users.city_id=C.id
					%donation_table%", "users.id");
	}

	return $data;
}

function getFromBothTables($select, $tables, $group_by) {
	global $filter, $top_count, $sql;
	
	$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, $top_count";

	$query = "SELECT $select FROM $tables $filter GROUP BY $group_by $order_and_limits";
	$donut_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.donation_amount) AS amount', 'INNER JOIN donations D ON D.fundraiser_id=users.id'), $query);
	$donut_data = $sql->getById($donut_query);

	$extdon_query = str_replace(array('%amount%', '%donation_table%'), array('SUM(D.amount) AS amount', 'INNER JOIN external_donations D ON D.fundraiser_id=users.id'), $query);
	$extdon_data = $sql->getById($extdon_query);

	$data = $donut_data;

	foreach ($extdon_data as $id => $value) {
		if(isset($data[$id])) $data[$id]['amount'] += $extdon_data[$id]['amount'];
	}

	usort($data, function($a, $b) {
		if($a['amount'] < $b['amount']) return 1;
		if($a['amount'] > $b['amount']) return -1;
		return 0;
	});

	return $data;
}

$html = new HTML;
render('index.php', false);
