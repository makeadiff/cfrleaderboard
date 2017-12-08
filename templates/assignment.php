<?php
$groups = array('0' => array('Any'));
foreach ($all_groups as $this_group_id => $group) {
	if(!isset($groups[$group['city_id']])) $groups[$group['city_id']] = array('Any');
	$groups[$group['city_id']][$this_group_id] = $group['name'];
}
?>
<script type="text/javascript">
var groups = <?php echo json_encode($groups); ?>;
</script>

<form action="" method="post" class="form-area" id="main-area">
<?php
$html->buildInput("madapp_city_id", 'City', 'select', $madapp_city_id, array('options' => $all_cities));
$html->buildInput("group_id", 'Group', 'select', $group_id, array('options' => $groups[$madapp_city_id]));
$html->buildInput("action", '', 'submit', 'Fetch');
?><br />


<table id="form-area">
<tr><th width="50%">MadApp</th><th width="50%">Donut</th></tr>
<tr><td>
<select name="madapp_users[]" id="madapp_users" multiple="multiple">
<?php
foreach ($all_city_users as $id => $name) {
	echo "<option value='$id'";
	if(in_array($id, array_keys($madapp_users_in_current_group))) echo " selected='selected'";
	echo ">$name</option>\n";
}
?>
</select><br />
<!-- <input type="button" value="Select All" id="select_all_madapp" /> -->
<input type="button" value="Copy Selected Users to Donut" id="madapp_to_donut" />
</td>
<td>
<select name="donut_group_users[]" id="donut_group_users" multiple="multiple">
<?php
foreach ($donut_users_in_current_group as $id => $name) {
	echo "<option value='$id'>$name</option>\n";
}
?>
</select>
<input type="button" value="Remove Selected Users from Donut Group" id="remove_from_donut" />
<input type="submit" name="action" value="Save Group" id="save" />
</td></tr>
</table>
</form>
