<!DOCTYPE HTML>
<html>
<head>
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, initial-scale=1.0, user-scalable=yes">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>CFR Leaderboard</title>
	<link rel="stylesheet" type="text/css" href="css/materialize.min.css" />
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/index.css" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<link rel="icon" href="favicon.ico" />
	<script type="text/javascript" src="js/jQuery2.js"></script>
	<script type="text/javascript" src="js/materialize.min.js"></script>
	<script type="text/javascript" src="js/script.js"></script>
	<script type="text/javascript" src="js/application.js"></script>
	<script type="text/javascript" src="js/index.js"></script>
</head>

<body>

	<?php include('header.php') ?>

	<div class="container">

	<div class="row">
		<form method="post" action="">
		<?php
		showOption("view_level", $all_view_levels, $view_level, "View Level");
		// showOption("timeframe", $all_timeframes, $timeframe, "Timeframe");
		// showOption("state_id", $all_states, $state_id, 'Region');
		showOption("city_id", $all_cities, $city_id, 'City');
		showOption("group_id", array(), $group_id, 'Center');
		// showOption("coach_id", array(), $coach_id, 'Coach');
		?>

		<div class="col offset-s4 s8 m3">
			<br/><br/><br/>
			<button class="btn waves-effect waves-light" type="submit" name="action">Submit
				<i class="material-icons right">send</i>
			</button>
			</div>

		</form>
	</div>

	<div class="row">
		<?php if(!$total_donation) { ?>
		<!-- <div class="col s12 m6">
			<div class="card">
				<div class="card-image">
					<img src="images/warning.png">
					<span class="card-title img-title">No data for the selected options</span>
				</div>
			</div>
		</div> -->
		<?php } ?>

		<?php if($all_levels['city']['data']) { ?>
		<div class="col s12 m6">
			<div class="card">
				<div class="card-image">
					<img src="images/city.jpg">
					<?php showCard('city'); ?>
				</div>
			</div>
		<?php } ?>

		<?php if($all_levels['group']['data']) { ?>
		<!-- <div class="col s12 m6">
			<div class="card">
				<div class="card-image">
					<img src="images/group.png">
					<?php //showCard('group'); ?>
				</div>
			</div> -->
		<?php } ?>

		<?php if($all_levels['user']['data']) { ?>
		<div class="col s12 m6">
			<div class="card">
				<div class="card-image">
					<img src="images/person.jpg">
					<?php showCard('user'); ?>
			</div>
		</div>
		<?php } ?>

		<?php if($total_donation and $timeframe == 0) { ?>

			<div class="col s12 m6" title="Amount : <?php echo money_format("%.0n", $total_donation)?>">
				<?php if($all_levels['user']['data']) { ?>
				<div class="card">
					<div class="card-image">
						<img src="images/child.jpg">
						<span class="card-title img-title">Amount Donuted <?php echo $all_levels['children_sponsored_title']?></span>
					</div>
					<div class="card-content">
						<p class="children_sponsored"><?php echo money_format("%.0n", $total_donation)?></p>
					</div>

				</div>
					<?php } ?>
			</div>

		<?php } ?>
	</div>

<script type="text/javascript">
// $(document).ready(function() {
// 	$('select').material_select();
// });
var menu = <?php echo json_encode($menu); ?>;
function pageInit() {
<?php
if($view_level == 'region') { echo "changeViewLevel('region');"; }
if($view_level == 'city') { echo "changeViewLevel('city');changeCity('$state_id');$('#city_id').val($city_id);"; }
if($view_level == 'group') { echo "changeViewLevel('group');changeCity('$state_id');changeGroup('$city_id');$('#group_id').val($group_id);"; }
?>
}
$(pageInit);
</script>

</body>
</html>
<?php
function show($key, $data, $title) {
?>
	<span class="card-title img-title"><?php echo $title ?></span>
</div>
<div class="card-content" id='top-<?php echo $key ?>'>
<table>
	<thead>
		<tr>
			<th width="5%"></th>
			<?php if($key!='user') {
				echo "<th width='25%'>Name</th>";
			}
			else{
				echo "<th width='50%'>Name</th>";
			}
			?>
			<?php if($key!='user') {
				echo "<th width='25%'>%Volunteer<br/>Participation</th>";
			}
			if($key=='city') {
				echo "<th width='25%'>%Potential<br/>Met</th>";
			}?>
			<th width="30%">Amount<br/>Donuted</th>
			<?php if($key=='user') {
				echo "<th width='25%'>Donor Count</th>";
			}
			?>
		</tr>
	</thead>
	<tr><td colspan="2">&nbsp;</td></tr>
<?php
$count = 1;
foreach ($data as $row) {
	if(!isset($row['name'])) continue; ?>
	<tr class="<?php if($count <= 3) echo 'show-row'; else echo 'hide-row'; ?>">
	<td width="5%"><?php if($count <= 3){ echo '<img src="./images/'.$count.'.png" height="15px" />'; } else echo ' '; ?></td>
  <td width="30%" class="unit-name" name="<?php echo $key ?>-name"><?php echo $count . '.  ' . ucwords(strtolower($row['name'])) ?></td>

	<?php if($key!='user') {
		echo "<td width='10%' title='{$row['user_count_participated']}/{$row['user_count_total']}'>";
		if(!isset($row['user_count_participated']) or $row['user_count_total'] == 0) echo "0";
		else echo number_format(round($row['participation_percentage'],0,PHP_ROUND_HALF_DOWN)) . "%";
		echo "</td>";
	}
	if($key == 'city') {
		echo "<td width='15%'>";
		if(!isset($row['target_percentage']) or $row['user_count_total'] == 0) echo "0";
		else echo $row['target_percentage'] . "%";
		echo "</td>";
	}
	?>
	<td width="25%" name="<?php echo $key ?>-raised"><?php echo money_format("%.0n", $row['amount']) ?></td>
	<?php
	if($key=='user') {
		echo "<td width='25%'>";
		if(!isset($row['donor_count']) or $row['donor_count'] == 0) echo "0";
		else echo $row['donor_count'];
		echo "</td>";
	}
	?>
</tr>
<?php
	$count++;
} ?>

</table><br />
<p class="activator"><a id='show-more-<?php echo $key ?>' class='toggle-link'> <i class="tiny material-icons">add</i>See More</a></p>

</div>

<?php
}


function showCard($key) {
	global $all_levels;
	show($key, $all_levels[$key]['data'], $all_levels[$key]['title']);
}


function showOption($id, $values, $selected, $title='') {
	global $html;
	?>
	<div class="col s12 m3" id="<?php echo $id ?>_area">
	<div class="input-field box-div">
		<?php
		$html->buildDropDownArray($values, $id, $selected);
		?>
		<label>Select <?php echo $title ?></label>
	</div>
	</div>
<?php
}
