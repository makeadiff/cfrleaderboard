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

	var window_width = $(window).width();
	var height_table = $('#table_data').height();

	if(window_width<=600){
		var width_image = $('.image_container').width();
		$('.image_container').css({
			left: (window_width/2)-(width_image/2)-30,
		})

		$('.imagebox').css({
			height:height+40+height_table+40
		})
	}

});

	