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
	<script type="text/javascript" src="js/vertical.js"></script>
</head>

<body>
	<?php include('header.php') ?>

	<div class="container">

	<div class="row">
		<form method="post" action="">
		<?php
		showOption("view_level", $all_view_levels, $view_level, "View Level");
		// showOption("timeframe", $all_timeframes, $timeframe, "Timeframe");
		showOption("vertical_id", $all_verticals, $vertical_id, 'Vertical');
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

		<div class="col s12 m6">
			<?php if($all_levels['vertical']['data']) { ?>
			<div class="card">
				<div class="card-image">
				<img src="images/region.jpg">
				<?php showCard('vertical'); ?>
			</div>
			<?php } ?>
		</div>

		<div class="col s12 m6">
			<?php if($all_levels['fellow']['data']) { ?>
			<div class="card">
				<div class="card-image">
					<img src="images/person.jpg">
					<?php showCard('fellow'); ?>
				</div>
			<?php } ?>
		</div>

	</div>

<script type="text/javascript">
	// $(document).ready(function() {
	// 	$('select').material_select();
	// });
	function pageInit() {
	<?php
	if($view_level == 'vertical') { echo "changeViewLevel('vertical');"; }

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
		<th width="40%">Name</th>
		<?php if($key=='vertical'){?>
		<th width="15%">% Fellow Participation</th>
		<th width="15%">% Potential Achieved</th>
		<?php }?>
		<th width="30%" style="text-align:right">Amount Raised</th>
		<?php if($key=='fellow'){?>
		<th width="20%" style="text-align:right">Donor Count</th>
		<?php }?>
		</tr>
	</thead>
	<tr><td colspan="2">&nbsp;</td></tr>
<?php
$count = 1;
foreach ($data as $row) { ?>
<tr class="<?php if($count <= 3) echo 'show-row'; else echo 'hide-row'; ?>">
	<td width="5%"><?php if($count <= 3){ echo '<img src="./images/'.$count.'.png" height="15px" />'; } else echo ' '; ?></td>

	<?php
		if($key!='vertical'){
			echo '<td width="30%" class="unit-name" name="'. $key.'-name">'.$count. '.  '. ucwords(strtolower($row['name'])).'</td>';
		}
		else{
			echo '<td width="40%" class="unit-name" name="'. $key.'-name">'.$count. '.  <a href="./vertical.php?view_level=vertical&vertical_id='.$row['id'].'">' . ucwords(strtolower($row['name'])).'</a></td>';
		}
	?>

	<?php if($key=='vertical') {
		echo "<td width='15%' title='{$row['user_count_participated']}/{$row['user_count_total']}'><strong>";
		if(!isset($row['user_count_participated']) or $row['user_count_total'] == 0) echo "0";
		else echo number_format(round($row['participation_percentage'],0,PHP_ROUND_HALF_DOWN)) . "%";
		echo "</strong></td>";

		echo "<td width='15%'>";
		if(!isset($row['target_percentage']) or $row['user_count_total'] == 0) echo "0";
		else echo $row['target_percentage'] . "%";
		echo "</td>";
	}?>
	<td width="25%" style="text-align:right"><?php echo money_format("%.0n", $row['amount']) ?></td>
	<?php if($key=='fellow'){?>
		<td width="30%" style="text-align:right"><?php echo $row['donor_count'] ?></td>
	<?php } ?>
</tr>
<?php
	$count++;
} ?>

</table><br />
<p class="activator" id="activator0"><a id='show-more-<?php echo $key.'-'.count($data) ?>' class='toggle-link'> <i class="tiny material-icons">add</i>See More</a></p>

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
