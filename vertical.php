<?php
require 'common.php';
include("../donutleaderboard/_city_filter.php");

$view_level = i($QUERY, 'view_level', 'national');
$timeframe 	= intval(i($QUERY, 'timeframe', '0'));
$view 		= i($QUERY, 'view', 'top');
$action 	= i($QUERY, 'action', '');
$vertical_id= i($QUERY, 'vertical_id', 0);

// $QUERY['no_cache'] = 1;
if($view_level != 'vertical') $vertical_id = 0;

setlocale(LC_MONETARY, 'en_IN');
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$year 			= 2016;
$cache_expire 	= 60 * 60;
$top_count 		= 30;
if($_SERVER['HTTP_HOST'] == 'makeadiff.in') {
	$db_madapp 		= 'makeadiff_madapp';
	$db_donut 		= 'makeadiff_cfrapp';
} else {
	$db_madapp 		= 'Project_Madapp';
	$db_donut 		= 'Project_Donut';
}

//Ignoring verticals that are not being used anymore
$all_verticals = $sql_madapp->getById("SELECT id,name FROM Vertical WHERE id NOT IN (1,6,10,11,12,13,14,15) ORDER BY name");

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
$all_levels = array('vertical' => $array_template, 'nt' => $array_template, 'fellow' => $array_template, 'coach' => $array_template /*, 'volunteer' => $array_template*/);
$all_levels['vertical']['show_in']	= array('national');
$all_levels['nt']['show_in']		= array('national', 'vertical');
$all_levels['fellow']['show_in']	= array('national', 'vertical');
$all_levels['coach']['show_in']		= array('national', 'vertical');
/*$all_levels['volunteer']['show_in']	= array('national', 'vertical');*/

foreach ($all_levels as $key => $level_info) {
	if(in_array($view_level, $level_info['show_in'])) {
		$name = ucfirst($key);
		if($name == 'Nt') $name = 'National Team';

		$title = 'Top ' . $name;

		if($vertical_id) {
			$vertical_name = $sql_madapp->getOne("SELECT name FROM Vertical WHERE id=$vertical_id");
			$title .= " in $vertical_name";
			$children_sponsored_title = " by " . $vertical_name;

		} else {
			$children_sponsored_title = "Nationally";
		}

		if($timeframe == '1') {
			$title .= " on " . date("jS M");

		} elseif($timeframe == '7') {
			$title .= " for last week(" . date("jS M", strtotime("last week")) . ")";
		}

		$all_levels[$key]['title'] = $title;
		$all_levels[$key]['data'] = getData($key);
		$all_levels['children_sponsored_title'] = $children_sponsored_title;
	}
}

//Get data for children sponsored

if(i($QUERY,'no_cache')) {
	$total_donation = 0;
	$total_count = 0;
} else {
	$total_donation = $mem->get("Infogen:index/total_donation#$timeframe,$view_level,$vertical_id");
	$total_count = $mem->get("Infogen:index/total_count#$timeframe,$view_level,$vertical_id");
}
$total_target = 4000 * 12000;

if(!$total_donation or !$total_count) {
	if ($view_level == 'national') {
		$data = getFromBothTables("%amount%", "users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "","AND (G.type = 'national' OR G.type = 'strat' OR G.type = 'fellow') AND R.id=9 AND UG.year = $year");

		$total_count = $sql_madapp->getById("SELECT G.type AS gtype, COUNT(*)
					FROM `$db_madapp`.User U
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
					INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
					WHERE U.status =1
						AND U.user_type =  'volunteer'
						AND UG.year = $year
						AND (G.type =  'national' OR G.type =  'strat' OR G.type =  'fellow')
					GROUP BY G.type");

	} elseif($view_level == 'vertical') {
		$data = getFromBothTables("%amount%", "users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "","AND (G.type = 'national' OR G.type = 'strat' OR G.type = 'fellow') AND R.id=9 AND UG.year = $year");

		$total_count = $sql_madapp->getById("SELECT G.type AS gtype, COUNT(*)
					FROM `$db_madapp`.User U
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
					INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
					WHERE U.status =1
					AND U.user_type =  'volunteer'
					AND UG.year = $year
					AND (G.type =  'national' OR G.type =  'strat' OR G.type =  'fellow')
					AND V.id = $vertical_id
					GROUP BY G.type");
 	}

	$total_donation = $data[0]['amount'] + isset($data[1]['amount']) ? $data[1]['amount'] : 0;

	$total_target = ($total_count['national'] * 20 * 6000) + ($total_count['strat'] * 16 * 6000) +($total_count['fellow'] * 6 * 6000);
	$mem->set("Infogen:index/total_donation#$timeframe,$view_level,$vertical_id", $total_donation, $cache_expire);
	$mem->set("Infogen:index/total_count#$timeframe,$view_level,$vertical_id", $total_count, $cache_expire);
}

function getData($key, $get_user_count = false) {
	global $timeframe,$view_level,$vertical_id, $mem, $QUERY, $cache_expire, $checks, $sql, $user_checks, $year, $db_madapp;

	if(i($QUERY,'no_cache')) {
		$data = array();
	} else {
		return $mem->get("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key");
	}

	if($key == 'vertical') {
		$data = getFromBothTables("IQ.vid as id,IQ.vname as name, %amount%", "users
					INNER JOIN
					(SELECT U.id as uid,U.name,V.id as vid,V.name as vname FROM `$db_madapp`.User U
						INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
						INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
						INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
						WHERE U.status = 1 AND U.user_type = 'volunteer' AND UG.year = $year 
							AND (G.type = 'national' OR G.type = 'strat' OR G.type = 'fellow') 
							AND V.id != 6
						GROUP BY U.id,V.id) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "IQ.vid");

	} elseif($key == 'nt') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type ='national' OR G.type = 'strat') AND V.id != 6 AND R.id=9 AND UG.year = $year");

	} elseif($key == 'fellow') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type = 'fellow')AND V.id != 6 AND R.id=9 AND UG.year = $year");

	} elseif($key == 'coach') {
		$data = getFromBothTables("manager.id,CONCAT(manager.first_name, ' ', manager.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "manager.id","AND (G.type = 'fellow') AND R.id=9 AND UG.year = $year");

	} elseif($key == 'volunteer') {
		$data = getFromBothTables("users.id,CONCAT(users.first_name, ' ', users.last_name) AS name, %amount%", "
					users
					INNER JOIN reports_tos RT ON RT.user_id=users.id
					INNER JOIN users AS manager ON RT.manager_id=manager.id
					INNER JOIN user_role_maps RM ON RM.user_id=manager.id
					INNER JOIN roles R ON R.id=RM.role_id
					INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = users.madapp_user_id
					INNER JOIN `$db_madapp`.Group G on G.id = UG.group_id
					INNER JOIN `$db_madapp`.Vertical V on V.id = G.vertical_id
					%donation_table%", "users.id","AND (G.type = 'fellow') AND R.id=9 AND UG.year = $year");
	}

	$mem->set("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key", $data, $cache_expire);

	return $data;
}

function getFromBothTables($select, $tables, $group_by = '', $where = '') {
	global $filter, $top_count, $sql, $checks;
	
	$order_and_limits = "ORDER BY amount DESC\nLIMIT 0, " . ($top_count * 20);

	if ($group_by == '') {
		$query = "SELECT $select FROM $tables $filter $where $order_and_limits";
	} else {
		$query = "SELECT $select FROM $tables $filter $where GROUP BY $group_by $order_and_limits";
	}

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

	return array_slice($data, 0, 30);
}

$html = new HTML;
render('vertical.php', false);
