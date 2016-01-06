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

setlocale(LC_MONETARY, 'en_IN');
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$year = 2015;
$cache_expire = 60 * 60;
$top_count = 8;
$all_states = $sql->getById("SELECT id,name FROM states");
$all_cities = $sql->getById("SELECT id,name FROM cities");
$all_view_levels = array('national' => "National", 'region' => "Region", 'city' => "City", 'group' => "Center"); // , 'coach' => "Coach"
$all_timeframes = array('1' => 'Day', '7' => 'Week', '0' => 'Overall');

$checks = array('is_deleted' => 'users.is_deleted=0');
if($state_id and $view_level == 'region')	$checks['state_id'] = "C.state_id=$state_id";
if($group_id and $view_level == 'group')	$checks['group_id'] = "manager.group_id=$group_id";
if($city_id  and $view_level == 'city')		$checks['city_id'] = "users.city_id=$city_id";
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
$all_levels = array('region' => $array_template, 'city' => $array_template, 'group' => $array_template, 'coach' => $array_template, 'user' => $array_template);
$all_levels['region']['show_in']	= array('national');
$all_levels['city']['show_in']		= array('national', 'region');
$all_levels['group']['show_in']		= array('national', 'region', 'city');
$all_levels['coach']['show_in']		= array('national', 'region', 'city', 'group');
$all_levels['user']['show_in']		= array('national', 'region', 'city', 'group', 'coach');

foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		$name = ucfirst($key);
		if($name == 'User') $name = 'Fundraiser';
		if($name == 'Group') $name = 'Center';

		$title = 'Top ' . $name;

		if($city_id) {
			$city_name = $sql->getOne("SELECT name FROM cities WHERE id=$city_id");
			$title .= " in $city_name";
		} elseif($state_id) {
			$state_name = $sql->getOne("SELECT name FROM states WHERE id=$state_id");
			$title .= " in $state_name";
		}

		if($timeframe == '1') {
			$title .= " on " . date("jS M");
		
		} elseif($timeframe == '7') {
			$title .= " for last week(" . date("jS M", strtotime("last week")) . ")";
		}

		$all_levels[$key]['title'] = $title;
		$all_levels[$key]['data'] = getData($key);
	}
}
$level_hirarchy = array_keys($all_view_levels);
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
$ecs_count_remaining = ceil($remaining_amount / 6000);

// Get the hirarchy
if(i($QUERY,'no_cache')) $menu = array();
else $menu = $mem->get("Infogen:index/menu");

if(!$menu) {
	foreach ($all_states as $this_state_id => $state_name) {
		$all_cities_in_state = $sql->getById("SELECT id, name FROM cities WHERE state_id=$this_state_id");
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
	global $timeframe,$view_level,$state_id,$city_id,$group_id, $mem, $QUERY, $cache_expire, $checks, $sql, $user_checks;

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

		if($get_user_count) {
			$user_count_data = $sql->getById("SELECT S.id, COUNT(users.id) AS count 
					FROM users 
					INNER JOIN cities C ON C.id=users.city_id
					INNER JOIN states S ON S.id=C.state_id
					WHERE " . implode(" AND ", $user_checks)
					. " GROUP BY S.id");

			foreach ($data as $key => $row) {
				$data[$key]['user_count'] = $user_count_data[$row['id']];
			}
		}
	} elseif($key == 'city') {
		$data = getFromBothTables("C.id,C.name, %amount%", "cities C
					INNER JOIN users ON city_id=C.id
					%donation_table%", "C.id");

		if($get_user_count) {
			$user_count_data = $sql->getById("SELECT C.id, COUNT(users.id) AS count 
					FROM users 
					INNER JOIN cities C ON C.id=users.city_id
					WHERE " . implode(" AND ", $user_checks)
					. " GROUP BY C.id");

			foreach ($data as $key => $row) {
				$data[$key]['user_count'] = $user_count_data[$row['id']];
			}
		}

	} elseif($key == 'group') {
		$data = getFromBothTables("G.id,G.name, %amount%", "users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users manager ON manager.id=RT.manager_id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN groups G ON G.id=manager.group_id
					INNER JOIN cities C ON C.id=users.city_id
					%donation_table%", "G.id", "AND R.id=9");

		if($get_user_count) {
			$user_count_data = $sql->getById("SELECT G.id, COUNT(users.id) AS count 
				FROM users 
				INNER JOIN reports_tos RT ON RT.user_id=users.id
				INNER JOIN users manager ON manager.id=RT.manager_id
				INNER JOIN user_role_maps RM ON RM.user_id=manager.id
				INNER JOIN roles R ON R.id=RM.role_id
				INNER JOIN groups G ON G.id=manager.group_id
				INNER JOIN cities C ON C.id=users.city_id
				WHERE R.id=9 AND " . implode(" AND ", $user_checks)
				. " GROUP BY G.id");

			foreach ($data as $key => $row) {
				$data[$key]['user_count'] = $user_count_data[$row['id']];
			}
		}



	} elseif($key == 'coach') {
		$data = getFromBothTables("manager.id,CONCAT(manager.first_name, ' ', manager.last_name) AS name, %amount%", "users
					%donation_table%
					INNER JOIN reports_tos RT ON RT.user_id=users.id 
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN cities C ON C.id=users.city_id", "manager.id", "AND R.id=9");

	} elseif($key == 'user') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "users 
					INNER JOIN cities C ON users.city_id=C.id
					INNER JOIN reports_tos RT ON RT.user_id=users.id 
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					%donation_table%", "users.id", "AND R.id=9");
	}

	$mem->set("Infogen:index/data#$timeframe,$view_level,$state_id,$city_id,$group_id,$key", $data, $cache_expire);

	return $data;
}

function getFromBothTables($select, $tables, $group_by, $where = '') {
	global $filter, $top_count, $sql, $checks;
	
	$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, $top_count";

	$query = "SELECT $select FROM $tables $filter $where GROUP BY $group_by $order_and_limits";
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

/*function money_format($format,$amount){
		return '<i class="fa fa-inr"></i>'.$amount;
}*/

