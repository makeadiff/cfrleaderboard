$(document).ready(function(){

	$('.activator').click(function(){
		var id = this.id;
		var class_to_unhide = ".more" + id[id.length-1] + "";
		//alert(class_to_unhide);
		$(class_to_unhide).slideDown('1000');
	});

});

	