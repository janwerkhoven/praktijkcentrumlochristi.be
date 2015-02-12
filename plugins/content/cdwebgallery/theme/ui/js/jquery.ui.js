/**
 * Core Design Web Gallery plugin
 */

jQuery(function($) {
	$('.webgallery_ui div.thumb').hover(
		function() {
			$(this).addClass('ui-state-hover');
		},
		function() {
			$(this).removeClass('ui-state-hover');
		}
	);
});