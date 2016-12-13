<script type="text/javascript">
var groups = <?php echo json_encode($groups); ?>;
</script>

<h3>Participation</h3>

<form action="" method="post" class="form-area">
<?php
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));

$html->buildInput("action", '&nbsp;', 'submit', 'Search', array('class' => 'btn btn-primary'));
?>
</form>


<?php if($data) { ?>
<table class="table table-striped">
<tr><th>Name</th><th>Amount</th><th>12K</th><th>30K</th><th>Participation</th><th>Target</th></tr>
<?php foreach ($data as $row) { ?>
<tr>
	<td>
	<?php if(!$city_id) { ?><a href="../exdon/aggregator.php?city_id=<?php echo $row['unit_id'] ?>&donation_status=any&action=Filter"><?php } ?>
	<?php echo $row['unit_name'] ?>
	<?php if(!$city_id) { ?></a><?php } ?></td>
	<td><?php echo money_format("%.0n", $row['total']) ?></td>
	<td><?php echo $row['12k'] ?></td>
	<td><?php echo $row['30k'] ?></td>
	<td title="<?php echo $row['participation'] .'/'. $row['total_user_count']; ?>"><?php echo $row['participation_percent'] ?> %</td>
	<td title="<?php echo $row['total'] .'/'. $row['target']; ?>"><?php echo $row['target_met_percent'] ?> %</td>
</tr>
<?php } ?>
</table>
<?php } ?>