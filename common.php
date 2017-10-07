<?php
require(dirname(dirname(__FILE__)) . '/common.php');
$sql->options['error_handling'] = 'die';

setlocale(LC_MONETARY, 'en_IN');
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$year = 2017;
$cache_expire = 60 * 60;
$top_count = 30;

$db_madapp 		= 'makeadiff_madapp';
$db_donut 		= 'makeadiff_cfrapp';
