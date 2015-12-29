<?php
require 'common.php';
include("../donutleaderboard/_city_filter.php");

$view_level = i($QUERY, 'view_level', 'national');
$time = i($QUERY, 'time', 'month');
$view = i($QUERY, 'view', 'top');
$action = i($QUERY, 'action', '');
$state_id = i($QUERY, 'state_id', 0);
$city_id = i($QUERY, 'city_id', 0);
$group_id = i($QUERY, 'group_id', 0);
$from = i($QUERY,'from', '2015-06-01');
$to = i($QUERY,'to', date('Y-m-d'));

setlocale(LC_MONETARY, 'en_IN');
$year = 2015;
$top_count = 8;
$all_states = $sql->getById("SELECT id,name FROM states");
$all_cities = $sql->getById("SELECT id,name FROM cities");
$all_view_levels = array('national' => "National", 'region' => "Region", 'city' => "City", 'group' => "Group", 'coach' => "Coach");

$checks = array('users.is_deleted=0');
if($state_id and $view_level == 'region')	$checks[] = "C.state_id=$state_id";
if($group_id and $view_level == 'group')	$checks[] = "users.group_id=$group_id";
if($city_id  and $view_level == 'city')		$checks[] = "users.city_id=$city_id";

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

function getData($key) {
	global $sql, $top_count, $filter;

	$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, $top_count";
	$data = array();

	if($key == 'region') {
		$data = $sql->getAll("SELECT S.id,S.name, SUM(D.donation_amount) AS amount 
				FROM states S
				INNER JOIN cities C ON C.state_id=S.id
				INNER JOIN users ON users.city_id=C.id
				INNER JOIN donations D ON D.fundraiser_id=users.id
				$filter 
				GROUP BY S.id
				$order_and_limits");

	} elseif($key == 'city') {
		$data = $sql->getAll("SELECT C.id,C.name, SUM(D.donation_amount) AS amount 
				FROM cities C
				INNER JOIN users ON city_id=C.id
				INNER JOIN donations D ON D.fundraiser_id=users.id
				$filter 
				GROUP BY C.id
				$order_and_limits");

	} elseif($key == 'group') {
		$data = $sql->getAll("SELECT G.id,G.name, SUM(D.donation_amount) AS amount 
				FROM users
				INNER JOIN groups G ON G.id=users.group_id
				INNER JOIN cities C ON C.id=users.city_id
				INNER JOIN donations D ON D.fundraiser_id=users.id
				$filter 
				GROUP BY G.id
				$order_and_limits");

	} elseif($key == 'coach') {
		$data = $sql->getAll("SELECT coach.id,CONCAT(coach.first_name, ' ', coach.last_name) AS name, SUM(D.donation_amount) AS amount 
				FROM users
				INNER JOIN donations D ON D.fundraiser_id=users.id
				INNER JOIN reports_tos RT ON RT.user_id=users.id 
				INNER JOIN users AS coach ON RT.manager_id=coach.id
				INNER JOIN cities C ON C.id=users.city_id
				$filter 
				GROUP BY coach.id
				$order_and_limits");

	} elseif($key == 'user') {
		$data = $sql->getAll("SELECT users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, SUM(D.donation_amount) AS amount 
				FROM users 
				INNER JOIN cities C ON users.city_id=C.id
				INNER JOIN donations D ON D.fundraiser_id=users.id
				$filter 
				GROUP BY C.id
				$order_and_limits");
	}

	return $data;
}

//dump($top_data, $bottom_data);
$html = new HTML;
render();
