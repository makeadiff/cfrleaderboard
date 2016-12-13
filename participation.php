<?php
require './common.php';
include("../donutleaderboard/_city_filter.php");

$page_title = 'Participation Dashboard';

$html = new HTML;
$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");
$all_cities[0] = 'All';
$all_groups = $sql->getById("SELECT id,name,city_id FROM groups ORDER BY FIELD(type, 'center', 'vertical'), name");

setlocale(LC_MONETARY, 'en_IN');

$groups = array('0' => array('Any'));
foreach ($all_groups as $this_group_id => $group) {
	if(!isset($groups[$group['city_id']])) $groups[$group['city_id']] = array();

	$groups[$group['city_id']][$this_group_id] = $group['name'];
}

$city_id	= i($QUERY, 'city_id', 0);

$extra_join = '';
$checks = array('is_deleted' => 'users.is_deleted=0');
if($city_id) $checks['city_id'] = "users.city_id=$city_id";

$from_date = $city_date_filter[25]['from']; // National start on date.

$where = "WHERE D.created_at >= '$from_date 00:00:00'";
// $checks['city_id'] = 'users.city_id = 13';
if($checks) $where .= " AND " . implode(" AND ", array_values($checks));

$unit_type = 'users.city_id AS unit_id';
$unit_type_field = 'city_id';
if($city_id) {
	$unit_type = 'users.group_id AS unit_id';
	$unit_type_field = 'group_id';
}

$query = "SELECT  users.id AS user_id, $unit_type, %amount_total%
			FROM %donation_table%
			INNER JOIN users ON D.fundraiser_id=users.id
			$extra_join
			$where
			GROUP BY users.id";

$donut_query = str_replace( array('%amount_total%', '%amount%', '%donation_table%'), 
							array('SUM(D.donation_amount) AS amount', 'D.donation_amount AS amount', 'donations D'), $query);
$donut_data = $sql->getAll($donut_query);

$extdon_query = str_replace(array('%amount_total%', '%amount%', '%donation_table%'), 
							array('SUM(D.amount) AS amount', 'D.amount', 'external_donations D'), $query);
$extdon_data = $sql->getAll($extdon_query);

// Initialize final data table.
$data = array();
$template_array = array(
					'unit_id'	=> 0, 
					'unit_name' => '', 
					'total'		=> 0, 
					'12k'		=> 0,
					'30k'		=> 0,
					'participation' => 0,

					'target'				=> 0,
					'target_met_percent'	=> 0,
					'participation_percent' => 0,
					'total_user_count'		=> 0,
				);

$unit_template = $all_cities;
if($city_id) $unit_template = $groups[$city_id];
foreach ($unit_template as $unit_id => $unit_name) {
	if(!$unit_id) continue;
	$data[$unit_id] = $template_array;
	$data[$unit_id]['unit_id'] = $unit_id;
	$data[$unit_id]['unit_name'] = $unit_name;
}

// Add all the data from both tables to the data array.
foreach ($donut_data as $row) addToData($row);
foreach ($extdon_data as $row) addToData($row);

// Now do the aggregation calculations.
foreach ($unit_template as $unit_id => $unit_name) {
	if(!$unit_id) continue;

	$data[$unit_id]['total_user_count'] = $sql->getOne("SELECT COUNT(id) FROM users WHERE $unit_type_field = $unit_id AND is_deleted='0'");
	$data[$unit_id]['target'] = $data[$unit_id]['total_user_count'] * 12000;

	$data[$unit_id]['target_met_percent'] = @round(($data[$unit_id]['total'] / $data[$unit_id]['target']) * 100, 2);
	$data[$unit_id]['participation_percent'] = @round(($data[$unit_id]['participation'] / $data[$unit_id]['total_user_count']) * 100, 2);
}


// dump($unit_template, $data);
render();

function addToData($row) {
	global $data;
	extract($row);
	if(!$unit_id or !isset($data[$unit_id])) return;

	$data[$unit_id]['total'] += $amount;
	if($amount > 12000) $data[$unit_id]['12k']++;
	if($amount > 30000) $data[$unit_id]['30k']++;

	$data[$unit_id]['participation']++;
}

