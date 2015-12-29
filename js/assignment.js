function init() {
	// Show selected city's centers. 
	$("#madapp_city_id").change(function() {
		var select = "<select id='group_id'>";
		var city_id = this.value;

		var centers_in_city = groups[city_id];
		for(var center_id in centers_in_city) {
			select += "<option value='"+center_id+"'>"+centers_in_city[center_id]+"</option>";
		}
		select += '</select>';

		$("#group_id").html(select);
	});
	$("#madapp_to_donut").click(copyUsersToDonut);
	$("#save").click(saveDonutUsers);
	$("#remove_from_donut").click(removeUserFromDonut);

}

function copyUsersToDonut() {
	$("#madapp_users option:selected").each(function() {
		// console.log(this.id + " : " + this.value)
		$("#donut_group_users").append("<option value='"+this.value+"'>"+this.innerHTML+"</option>");
	})
}

function saveDonutUsers() {
	$("#donut_group_users option").prop("selected", true);
	// $("#action").val("Save Group");
	// $("#main-area").submit();
}

function removeUserFromDonut() {
	$("#donut_group_users option:selected").remove();
}