/**
 * Core Design Web Gallery plugin
 */

jQuery(document).ready(function($){
	var images = $('a[rel*=webgallery_tooltip]');
	images.click( function () {
		return false;
	}).tooltip({ 
	    delay: 0,
	    fade: 250,
	    top: 10,
	    left: 10,
	    track: true, 
	    showURL: false, 
	    bodyHandler: function() {
	    	var title = $(this).children('img').attr('title');				    
	    	var img = '<div class=\"cdwebgallery_tooltip\"><img src=\"'+this.href+'\" title=\"\" alt=\"\" /><span>' + title + '<\/span><\/div>';
		    	return img;
	    	} 
		});
});