var current_state_id, current_city_id, current_group_id;

function init() {
	$(".toggle-link").click(showMore)

	$("#view_level").change(changeViewLevel);
}

function showMore() {
	var key = this.id.replace(/show\-more\-/, '');
	$("#top-" + key + " .hide-row").toggle('fade');

	var link = $(this);

	if(!link.hasClass("currently-active")) {
		link.addClass("currently-active");
		link.html(' <i class="tiny material-icons">remove</i>See Less');
	} else {
		link.removeClass("currently-active");
		link.html(' <i class="tiny material-icons">add</i>See More');
	}
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



