function init() {
	$(".toggle-link").click(showMore)
}

function showMore() {
	var key = this.id.replace(/show\-more\-/, '');
	$("#top-" + key + " .hide-row").removeClass("hide-row");
	$(this).hide();
}

