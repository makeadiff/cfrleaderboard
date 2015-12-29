<?php

$city_transilation = array(
		// Madapp City ID 		=> Donut City ID
		'26'	=> '25',
		'24'	=> '13',
		'1'		=> '44',	// Blore
		'21'	=> '12',
		'13'	=> '21',
		'6'		=> '14',
		'10'	=> '3',		// Cochin
		'16'	=> '19',
		'25'	=> '24',
		'12'	=> '20',
		'23'	=> '18',
		'19'	=> '23',
		'11'	=> '17',
		'14'	=> '11',
		'20'	=> '22',
		'2'		=> '4',
		'4'		=> '9',
		'22'	=> '5',
		'15'	=> '8',
		'5'		=> '10',
		'3'		=> '15',
		'8'		=> '6',
		'18'	=> '16',
		'17'	=> '7',
		'29'	=> '25',
		'30'	=> '25',
		'31'	=> '25',
		'32'	=> '25',
	);

function city_transilation_donut_to_madapp($city_id) {
	global $city_transilation;
	foreach ($city_transilation as $madapp_city_id => $donut_city_id) {
		if($donut_city_id == $city_id) return $madapp_city_id;
	}
	return 0;
}