<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta name="viewport" content="width=device-width, minimum-scale=1.0, initial-scale=1.0, user-scalable=yes">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Donut</title>
	<link rel="stylesheet" type="text/css" href="css/materialize.min.css" />
	<link rel="stylesheet" type="text/css" href="css/style.css" />    
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
				 <a href="#" class="brand-logo">&nbsp; &nbsp;Donut</a>
				 <ul id="nav-mobile" class="right hide-on-med-and-down">
					<li><a href="./donations">National Dashboard</a></li>
					<li><a href="./donations">City Dashboard</a></li>
				 </ul>
			</div>
		</nav>
	</div>

	<div class="row">  
		<form method="post" action="">
		<div class="col s12 m3" >
			
			<div class="input-field box-div">
				<?php 
				$html->buildDropDownArray($all_view_levels, "view_level", $view_level);
				?>
				<label>Select Level</label>
			</div>
		</div>
		
		<div class="col s12 m3">
			<div class="input-field box-div">
				<?php 
				$html->buildDropDownArray($all_timeframes, "timeframe", $timeframe);
				?>
				<label>Select Timeframe</label>
			</div>
		</div>

		<div class="col s12 m3">
			<div class="input-field box-div">
				<?php 
				$html->buildDropDownArray($all_cities, "city_id", $city_id);
				?>
				<label>Select City</label>
			</div>
		</div>

		<div class="col s12 m3">
			<br/><br/>
			<input type="submit" class="waves-effect waves-light btn-large" value="Submit"/>
		</div>
		</form>
	</div>
	
	<div class="row">
		<div class="col s12 m6">
			<?php if($all_levels['region']['data']) { ?>
			<div class="card">
				<?php showCard('region'); ?>
			</div>
			<?php } ?>
		</div> 

		<div class="col s12 m6">
			<?php if($all_levels['city']['data']) { ?>
			<div class="card">
				<?php showCard('city'); ?>
			</div>
			<?php } ?>
		</div>
	</div>
	<div class="row">
		<div class="col s12 m6">
			<?php if($all_levels['group']['data']) { ?>
			<div class="card">
				<?php showCard('group'); ?>
			</div>
			<?php } ?>
		</div>

		<div class="col s12 m6">
			<?php if($all_levels['user']['data']) { ?>
			<div class="card">
				<?php showCard('user'); ?>
			</div>
			<?php } ?>
		</div>
 
	</div>    
</body>

</html>

<script type="text/javascript">
	$(document).ready(function() {
		$('select').material_select();
	});
</script>

<?php 
function show($key, $data, $title) {
?>
<div class="card-content">
<span class="card-title activator grey-text text-darken-4"><?php echo $title ?></span>
<table>
	<thead>
		<th width="50%">Name</th>
		<th width="50%">Amount Raised</th>
	</thead>
<?php 
$count = 1;
foreach ($data as $row) { ?>
<tr class="<?php if($count <= 3) echo 'show-row'; else echo 'hide-row'; ?>">
	<td width="50%"><?php echo $count . '. ' . $row['name'] ?></td>
	<td width="50%"><?php echo money_format("%n", $row['amount']) ?></td>
</tr>
<?php 
	$count++;
} ?>	
</table>
<p class="activator" id="activator-<?php echo $key ?>"><a href="#"  id='show-more-<?php echo $key ?>' class='toggle-link'> <i class="tiny material-icons">add</i>See More</a></p>
</div>

<?php
}

function showCard($key) {
	global $all_levels;
	show($key, $all_levels[$key]['data'], $all_levels[$key]['title']);
}
