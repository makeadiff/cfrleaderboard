<?php
//Configuration file for iFrame
$config = array(
	'site_title'	=> 'InfoGen',
	'db_database'	=> (isset($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] == 'makeadiff.in') ? 'makeadiff_cfrapp' : 'makeadiff_cfrapp',
) + $config_data;
$config['site_home'] = $config_data['site_home'] . 'apps/cfrleaderboard/';

// var_dump($config);
