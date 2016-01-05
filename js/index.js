var current_state_id, current_city_id, current_group_id;

function init() {
	$(".toggle-link").click(showMore)

	$("#view_level").change(changeViewLevel);
	$("#state_id").change(changeCity);
	$("#city_id").change(changeGroup);
	$("#group_id").change(changeCoach);
}

function showMore() {
	var key = this.id.replace(/show\-more\-/, '');
	$("#top-" + key + " .hide-row").removeClass("hide-row");
	$(this).hide();
}

function changeViewLevel(view_level_arg) {
	if(typeof view_level_arg == "string") var view_level = view_level_arg;
	else var view_level = $(this).val();

	if(view_level == 'region') {
		$("#state_id_area").show();
		$("#city_id_area").hide();
		$("#group_id_area").hide();
		$("#coach_id_area").hide();

	} else if(view_level == 'city') {
		$("#state_id_area").show();
		$("#city_id_area").show();
		$("#group_id_area").hide();
		$("#coach_id_area").hide();

		changeCity("3");
		$('#state_id').val(3);
	
	} else if(view_level == 'group') {
		$("#state_id_area").show();
		$("#city_id_area").show();
		$("#group_id_area").show();
		$("#coach_id_area").hide();

		changeCity("3");
		changeGroup("3");
		$('#state_id').val(3);
		$('#city_id').val(3);

	} else if(view_level == 'coach') {
		$("#state_id_area").show();
		$("#city_id_area").show();
		$("#group_id_area").show();
		$("#coach_id_area").show();

		changeCity("3");
		//changeGroup("3");
		//changeCoach("3");
		$('#state_id').val(3);
		$('#city_id').val(3);
		$('#group_id').val(3);
		
	}
}


function changeCity(state_id) {
	if(typeof state_id != "string") state_id = this.value;
	$("#state_id").val(state_id);
	current_state_id = state_id;
	populateSelect("city_id", menu[state_id]['cities']);
}
function changeGroup(city_id) {
	if(typeof city_id != "string") city_id = this.value;
	$("#city_id").val(city_id);
	current_city_id = city_id;
	populateSelect("group_id", menu[current_state_id]['cities'][city_id]['groups']);
}
function changeCoach(group_id) {
	if(typeof group_id != "string") group_id = this.value;
	$("#group_id").val(group_id);
	current_group_id = group_id;
	populateSelect("coach_id", menu[current_state_id]['cities'][current_city_id]['groups'][group_id]['users']);
}

function populateSelect(element_id, options) {
	var select = $("#" + element_id)
	select.find('option').remove();

	for(var opt in options) {
		select.append($("<option value='" + options[opt]['id'] + "'>"+options[opt]['name']+"</option>"));
	}
}
