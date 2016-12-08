<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('City', 'Raised'));

$data = file_get_contents('http://localhost/makeadiff.in/home/makeadiff/public_html/apps/cfrleaderboard/');
$regex = '/<td width="60%" class="unit-name" name="city-name">[0-9]*.[\s]*(.+?)<\/td>[\n\r\s]+<td width="25%" name="city-raised">₹ (.+?)<\/td>/';
preg_match_all($regex,$data,$match);
for($i=0;$i< sizeof($match[2]);$i++) {

    $match[2][$i] = str_replace(",","",$match[2][$i]);

}

for($i=0;$i< sizeof($match[2]);$i++) {

    fputcsv($output,array($match[1][$i],$match[2][$i]));

}

?>