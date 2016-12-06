<?php
require 'common.php';

if($config['server_host'] != 'cli') die("Should be run as a command.");

// DB change
// ALTER TABLE `groups` ADD `type` ENUM('center','vertical','other') NOT NULL DEFAULT 'center' AFTER `city_id`, ADD `madapp_id` INT(11) UNSIGNED NOT NULL AFTER `type`; 
// 
$year = 2016;

if(isset($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] == 'makeadiff.in') {
	$sql_donut = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], "makeadiff_cfrapp");
	$sql_madapp= new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], "makeadiff_madapp");
} else {
	$sql_donut = new Sql("Project_Donut");
	$sql_madapp= new Sql("Project_Madapp");
}

$sql_donut->execQuery("TRUNCATE groups"); // Are you sure?
$sql_donut->execQuery("UPDATE users SET group_id=0"); // Reset existing connections;

$all_cities = $sql_madapp->getById("SELECT id,name FROM City WHERE type='actual'");
$all_cities[26] = 'Leadership';

$all_verticals = $sql_madapp->getById("SELECT id,name FROM Vertical WHERE id IN (2,4,5,7,8,9,17)"); // Ed Support is ignored because that will show up in the batch connection

foreach ($all_cities as $city_id => $city_name) {
	$all_centers_in_city = $sql_madapp->getById("SELECT id,name FROM Center WHERE city_id=$city_id AND status='1'");
	$donut_city_id = $city_transilation[$city_id];

	print "City: $city_name\n";
	foreach ($all_centers_in_city as $center_id => $center_name) {
		print "\t$center_name: ";
		// Insert Centers into groups table
		$group_id = $sql_donut->execQuery("INSERT INTO groups(name,city_id,type,madapp_id) VALUES('".$sql_donut->escape($center_name)."', $donut_city_id, 'center', $center_id)");

		$madapp_users_in_current_group = $sql_madapp->getById("SELECT U.id,U.name FROM User U 
				INNER JOIN UserBatch UB ON UB.user_id=U.id
				INNER JOIN Batch B ON B.id=UB.batch_id
				INNER JOIN Center C ON C.id=B.center_id
				WHERE C.id='$center_id' AND U.status='1' AND U.user_type='volunteer' AND B.year=$year");

		foreach ($madapp_users_in_current_group as $madapp_user_id => $user_name) {
			$sql_donut->execQuery("UPDATE users SET group_id=$group_id WHERE madapp_user_id=$madapp_user_id");
		}

		print "Inserted " . count($madapp_users_in_current_group) . " users.\n";
	}

	foreach ($all_verticals as $vertical_id => $vertical_name) {
		print "\tVertical $vertical_name: ";
		// Insert Verticals into groups table
		$group_id = $sql_donut->execQuery("INSERT INTO groups(name,city_id,type,madapp_id) VALUES('".$sql_donut->escape($vertical_name)."',$donut_city_id,'vertical',$vertical_id)");

		$madapp_users_in_vertical = $sql_madapp->getById("SELECT U.id,U.name FROM User U 
				INNER JOIN UserGroup UG ON UG.user_id=U.id
				INNER JOIN `Group` G ON G.id=UG.group_id
				INNER JOIN Vertical V ON V.id=G.vertical_id
				WHERE V.id='$vertical_id' AND U.city_id=$city_id AND U.status='1' AND U.user_type='volunteer' AND UG.year=$year");

		foreach ($madapp_users_in_vertical as $madapp_user_id => $user_name) {
			$sql_donut->execQuery("UPDATE users SET group_id=$group_id WHERE madapp_user_id=$madapp_user_id");
		}
		print "Inserted " . count($madapp_users_in_vertical) . " users.\n";
	}
}
