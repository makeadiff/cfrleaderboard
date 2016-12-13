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
$verticals_to_hide = array(6,10,11,12,13,14,15,16);
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
		$data = getFromBothTables("IQ.vid AS id,IQ.vname AS name, %amount%", "
					users
					INNER JOIN 
					(SELECT U.id AS uid,U.name,V.id AS vid,V.name AS vname 
						FROM `$db_madapp`.User U
						INNER JOIN `$db_madapp`.UserGroup UG ON UG.user_id = U.id
						INNER JOIN `$db_madapp`.`Group` G ON G.id = UG.group_id
						INNER JOIN `$db_madapp`.Vertical V ON V.id = G.vertical_id
						WHERE U.status = 1 AND U.user_type = 'volunteer' AND UG.year = $year
						GROUP BY U.id,V.id) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "IQ.vid");
		// dump($data);

	} elseif($key == 'fellow') {
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
						GROUP BY U.id,V.id) IQ
					ON IQ.uid = users.madapp_user_id
					%donation_table%", "users.id","");
	}

	$mem->set("Infogen:index/data#$timeframe,$view_level,$vertical_id,$key", $data, $cache_expire);

	return $data;
}

/// This is kind of horrible. But there was a lot of code repetation earlier - so I switched to this approch. Need as have to do the same check on two seperate tables.
function getFromBothTables($select, $tables, $group_by = '', $where = '') {
	global $top_count, $sql, $checks, $city_checks;
	
	$order_and_limits = ''; //"ORDER BY amount DESC\nLIMIT 0, " . ($top_count * 20);

	$filter = "WHERE $city_checks";
	if($checks) $filter .= " AND " . implode(" AND ", array_values($checks));

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
		else $data[$id] = $extdon_data[$id];
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
