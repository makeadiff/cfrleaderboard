var current_state_id, current_city_id, current_group_id;

function init() {

	var show_more_count = [];
	show_more_count['vertical'] = 3;
	show_more_count['nt'] = 3;
	show_more_count['fellow'] = 3;

	$(".toggle-link").click({show_more_count : show_more_count}, showMore)

	$("#view_level").change(changeViewLevel);
}

function showMore(event) {
	var show_more_count = event.data.show_more_count;
	var string = this.id.replace(/show\-more\-/, '');
	var parameters = string.split('-');
	var key = parameters[0];
	var count = parameters[1];
	var link = $(this);

	if(show_more_count[key] > count) {
		// $("#top-" + key + " .hide-row").hide('fade');
		show_more_count[key] = 3;
		link.html(' <i class="tiny material-icons">add</i>See More');
	} else {
		$("#top-" + key + " .hide-row:lt(" + show_more_count[key] + ")").show('fade');
		show_more_count[key] += 9;
	}

	if(show_more_count[key] > count) {
		link.html(' <i class="tiny material-icons">remove</i>See Less');
	}

	/*if(!link.hasClass("currently-active")) {
	 link.addClass("currently-active");
	 link.html(' <i class="tiny material-icons">remove</i>See Less');
	 } else {
	 link.removeClass("currently-active");
	 link.html(' <i class="tiny material-icons">add</i>See More');
	 }*/
}

function changeViewLevel(view_level_arg) {
	if(typeof view_level_arg == "string") var view_level = view_level_arg;
	else var view_level = $(this).val();

	if(view_level == 'vertical') {
		$("#vertical_id_area").show();

	} else if(view_level == 'national') {
		$("#vertical_id_area").hide();

	}
}
