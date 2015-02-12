jQuery(document).ready(function($){
	$('a[rel^=webgallery_popup]').click( function (e) {
		e.preventDefault();
		var rel_attr = $(this).attr('rel').split(' ');
		var dimension = rel_attr[1].split('x');
		var width = (dimension[0] * 1) + 20;
		var height = (dimension[1] * 1) + 20;
		var link = $(this).attr('href');
		window.open( link, 'webgallery_popup', 'left=20, top=20, height=' + height + ', width=' + width + '' );
	});
});