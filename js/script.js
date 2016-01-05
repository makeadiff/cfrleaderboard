$(document).ready(function(){

	$('.activator').click(function(){
		var id = this.id;
		var class_to_unhide = ".more" + id[id.length-1] + "";
		//alert(class_to_unhide);
		$(class_to_unhide).slideDown('1000');
	});

	var width = $('#image_over').width();
	var height = $('#image_over').height();

	$('.image_container').css({
		width:width,
		height:height
	});
});

	