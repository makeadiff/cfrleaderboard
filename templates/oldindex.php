<form action="" method="post" class="form-area">
<?php
$html->buildInput("view_level", 'View', 'select', $view_level, array('options' => $all_view_levels));
$html->buildInput("state_id", 'Region', 'select', $state_id, array('options' => $all_states));
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br />

<?php

foreach ($all_levels as $key => $value) {
	if($value['data'])
		show($key, $value['data'], $value['title']);
}

function show($key, $data, $title) {
?>
<h3><?php echo $title ?></h3>

<table class="table table-striped" id='top-<?php echo $key ?>'>
<tr><th width="50%">Name</th><th width="50%">Amount Raised</th></tr>
<?php 
$count = 1;
foreach ($data as $row) { ?>
<tr class="<?php if($count <= 3) echo 'show-row'; else echo 'hide-row'; ?>"><td><?php echo $row['name'] ?></td><td><?php echo money_format("%n", $row['amount']) ?></td></tr>
<?php 
	$count++;
} ?>
</table>
<a href="#" id='show-more-<?php echo $key ?>' class='toggle-link'>Show More...</a>

<?php
}