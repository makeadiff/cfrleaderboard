<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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
	<div class="navbar-fixed">
		<nav>
			<div class="nav-wrapper">
				 <a href="#" class="brand-logo center-align">&nbsp; &nbsp;#LetMADBreathe</a>
				 <ul id="nav-mobile" class="right hide-on-med-and-down">
				 </ul>
			</div>
		</nav>
	</div>

	<div class="container">

	<div class="row">  
		<form method="post" action="">
		<?php
		showOption("view_level", $all_view_levels, $view_level, "View Level");
		showOption("timeframe", $all_timeframes, $timeframe, "Timeframe"); 
		showOption("state_id", $all_states, $state_id, 'Region');
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
		<div class="col s12 m6">
			<div class="card">
				<div class="card-image">
					<img src="images/warning.png">
					<span class="card-title img-title">No data for the selected options</span>
				</div>
			</div>
		</div>
		<?php } ?>

		<div class="col s12 m6">
			<?php if($all_levels['region']['data']) { ?>
			<div class="card">
				<div class="card-image">
					<img src="images/region.jpg">
					<?php showCard('region'); ?>
			</div>
			<?php } ?>
		</div> 

		<div class="col s12 m6">
			<?php if($all_levels['city']['data']) { ?>
			<div class="card">
				<div class="card-image">
					<img src="images/city.jpg">
					<?php showCard('city'); ?>
			</div>
			<?php } ?>
		</div>

			<div class="col s12 m6">
				<?php if($all_levels['group']['data']) { ?>
				<div class="card">
					<div class="card-image">
						<img src="images/group.png">
						<?php showCard('group'); ?>
					</div>
					<?php } ?>

			</div>


		<div class="col s12 m6">
			<?php if($all_levels['coach']['data']) { ?>
			<div class="card">
				<div class="card-image">
					<img src="images/coach.jpg">
					<?php showCard('coach'); ?>
				</div>
				<?php } ?>
			</div>

		<div class="col s12 m6">
			<?php if($all_levels['user']['data']) { ?>
			<div class="card">
				<div class="card-image">
					<img src="images/person.jpg">
					<?php showCard('user'); ?>
			</div>
			<?php } ?>
		</div>

		<?php if($total_donation and $timeframe == 0) { ?>

			<div class="col s12 m6">
				<?php if($all_levels['user']['data']) { ?>
				<div class="card">
					<div class="card-image">
						<img src="images/child.jpg">
						<span class="card-title img-title">Children Sponsored <?php echo $all_levels['children_sponsored_title']?></span>
					</div>
					<div class="card-content">
						<p class="children_sponsored"><?php echo number_format(round($total_donation/12000,0,PHP_ROUND_HALF_DOWN)); ?> / <?php echo number_format($all_levels['children_count']); ?></p>
					</div>

				</div>
					<?php } ?>
			</div>
		<?php } ?>

	</div>

	<?php if($total_donation and $timeframe == 0) { ?>
	<div class="row">
		<div class="col s12 m12">
			<div class="card">
				<div class="card-content imagebox">
					<div class="image_container">
						<div class="popup">
							<div class="container_fill" style="height:<?php echo $percentage_done ?>%; top:<?php echo 100 - $percentage_done ?>%"> <!-- Change the percentage Values here. -->
							</div>
							<img src="images/oxycyl.png" id="image_over" alt="Cylinder" >
							<p id="cylinder-info" title="Target: <?php echo $target_amount ?>. Raised So Far : <?php echo $total_donation ?>. Total Volunteers : <?php echo $total_user_count ?>"><?php echo $ecs_count_remaining ?> <br/>ECS<br>to target.</p>
							<?php if($oxygen_card_data) { ?>
							<div id="table_data">
								<table>
									<thead>
										<tr>
										<th>Center</th>
										<th>Current Status</th>
										</tr>
									</thead>
									<?php foreach ($oxygen_card_data as $row) {
										$unit_target_amount = (($row['user_count'] * 70 / 100) * 12000) + (floor($row['user_count'] * 5 / 100) * 100000);
										$unit_remaining_amount = $unit_target_amount - $row['amount'];
										$unit_percentage_done = 0;
										if($unit_target_amount) $unit_percentage_done = round($row['amount'] / $unit_target_amount * 100, 2);
										$unit_ecs_count_remaining = ceil($unit_remaining_amount / 6000);

										?>
									<tr>
										<td><?php echo $row['name'] ?></td>
										<td>
											<div class="histo-container" title="Target: <?php echo $unit_target_amount ?>. Raised So Far : <?php echo $row['amount'] ?>. Total Volunteers : <?php echo $row['user_count'] ?>">
												<?php echo $unit_ecs_count_remaining ?> ECS to target.
												<div class="histogram" style="width:<?php echo $unit_percentage_done ?>%; float:left;">
												
												</div>        
											</div>
										</td>
									</tr>
										
									<?php } ?>
								</table>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

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
		<th width="45%">Name</th>
		<th width="50%">Amount Raised</th>
		</tr>
	</thead>
	<tr><td colspan="2">&nbsp;</td></tr>
<?php 
$count = 1;
foreach ($data as $row) { ?>
<tr class="<?php if($count <= 3) echo 'show-row'; else echo 'hide-row'; ?>">
	<td width="5%"><?php if($count <= 3){ echo '<img src="./images/'.$count.'.png" height="15px" />'; } else echo ' '; ?></td>
	<td width="65%" class="unit-name"><?php echo $count . '.  ' . $row['name'] ?></td>
	<td width="30%"><?php echo money_format("%.0n", $row['amount']) ?></td>
</tr>
<?php 
	$count++;
} ?>	

</table><br />
<p class="activator" id="activator0"><a id='show-more-<?php echo $key ?>' class='toggle-link'> <i class="tiny material-icons">add</i>See More</a></p>

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
