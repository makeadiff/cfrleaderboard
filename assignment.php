<?php
require 'common.php';

$sql_donut = new Sql("Project_Donut");
$sql_madapp= new Sql("Project_Madapp");

// Argument Parsing.
$madapp_city_id = i($QUERY,'madapp_city_id', 10);
$donut_city_id = $city_transilation[$madapp_city_id];
$group_id = i($QUERY,'group_id', 13);
$action = i($QUERY, 'action', '');

$year = 2015;
$group_name = '';
$madapp_users_in_current_group = array();

// if(!$city_id) die("No city specified");

$all_cities = $sql_madapp->getById("SELECT id,name FROM City");
$all_groups = $sql_donut->getById("SELECT id,name,city_id AS donut_city_id FROM groups");
$all_verticals = $sql_madapp->getById("SELECT id,name FROM Vertical");

foreach ($all_groups as $this_group_id => $group) {
	$all_groups[$this_group_id]['city_id'] = city_transilation_donut_to_madapp($group['donut_city_id']);
}

if($action == 'Save Group') {
	$selected_users = $QUERY['donut_group_users'];
	$group_id = $QUERY['group_id'];
	$donut_users_ids = $sql_donut->getById("SELECT madapp_user_id, id FROM users WHERE madapp_user_id IN (" . implode(',', $selected_users) . ")");
	foreach ($donut_users_ids as $madapp_id => $user_id) {
		$sql_donut->execQuery("UPDATE users SET group_id=$group_id WHERE id=$user_id ");
	}
	$action = 'Fetch';
}

if($action == 'Fetch') {
	$group_name = $all_groups[$group_id]['name'];

	$all_city_users = $sql_madapp->getById("SELECT U.id,U.name FROM User U 
				WHERE U.city_id=$madapp_city_id AND U.status='1' AND U.user_type='volunteer'
				ORDER BY U.name");
	
	$donut_users_in_current_group = $sql_donut->getById("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM users
				WHERE group_id=$group_id
				ORDER BY name");

	if(in_array($group_name, $all_verticals)) {
		$madapp_users_in_current_group = $sql_madapp->getById("SELECT U.id,U.name FROM User U 
				INNER JOIN UserGroup UG ON UG.user_id=U.id
				INNER JOIN `Group` G ON G.id=UG.group_id
				INNER JOIN Vertical V ON V.id=G.vertical_id
				WHERE V.name='$group_name' AND U.city_id=$madapp_city_id AND U.status='1' AND U.user_type='volunteer' AND UG.year=$year");
	} else {
		$madapp_users_in_current_group = $sql_madapp->getById("SELECT U.id,U.name,C.name AS center FROM User U 
				INNER JOIN UserBatch UB ON UB.user_id=U.id
				INNER JOIN Batch B ON B.id=UB.batch_id
				INNER JOIN Center C ON C.id=B.center_id
				WHERE C.name='$group_name' AND C.city_id=$madapp_city_id AND U.status='1' AND U.user_type='volunteer' AND B.year=$year");
		
	}
}


$html = new HTML;
render();