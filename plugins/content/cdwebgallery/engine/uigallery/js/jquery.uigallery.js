/**
 * Core Design Web Gallery plugin for Joomla! 1.5
 * @author		Daniel Rataj, <info@greatjoomla.com>
 * @package		Joomla
 * @subpackage	Content
 * @category   	Plugin
 * @version		1.1.0
 * @copyright	Copyright (C) 2007 - 2010 Great Joomla!, http://www.greatjoomla.com
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL 3
 * 
 * This file is part of Great Joomla! extension.   
 * This extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

(function($) {

	// function $(something).uigallery();
	$.fn.uigallery = function(opt) {

		// Default values
		var defaults = {
			theme: 'smoothness'
		};
		var settings = $.extend(defaults, opt);

		var image = $(this);

		return image.each(function(n, el) {

			// set prev and next links
			var nextlink = image[(n + 1)];
			var prevlink = image[(n - 1)];

			var rel_attr = $(el).attr('rel').split('x');

			var width = rel_attr[0] * 1 + 50;
			var height = rel_attr[1] * 1 + 50;

			$(el).click( function (e) {
				e.preventDefault();

				el = $(this);

				var img = el.children('img');

				// preload prev image
				if (prevlink != undefined) {
					prevImage = new Image();
					prevImage.src = $(prevlink).attr('href');
				}

				// preload next image
				if (nextlink != undefined) {
					nextImage = new Image();
					nextImage.src = $(nextlink).attr('href');
				}

				// prevent category and section blog preview
				var enableDialog = $('#uigallery', 'body');
				if (enableDialog.length) return false;

				var uigallery = $('<div />', {
					id : 'uigallery'
				}).appendTo('body');
				
				uigallery.dialog({
					title: img.attr('title'),
					height: height,
					width: width,
					draggable: false,
					resizable: false,
					stack: false,
					buttons: {
						'«': function() {
							$(this).dialog('close');
							$(prevlink).click();
						},
						'»': function() {
							$(this).dialog('close');
							$(nextlink).click();
						}
					},
					open: function() {

						uigallery = $(this);
						
						theme($(this)); // add theme
						
						// disable next button if image is last one
						var link_next = image[(n + 1)];
						if (link_next == undefined) {
							$('button:eq(1)', $('div.ui-dialog-buttonpane', $(this).dialog('widget'))).button('disable');
						}

						// disable prev button if image is last one
						var link_prev = image[(n - 1)];
						if (link_prev == undefined) {
							$('button:eq(0)', $('div.ui-dialog-buttonpane', $(this).dialog('widget'))).button('disable');
						}

						var counter = '<div class="counter">' + (n + 1) + '/' + image.length + '</div>';

						$('div.ui-dialog-buttonpane', $(this).dialog('widget')).prepend(counter);

						var imgtag = '<img src="' + el.attr('href') + '" title="' + img.attr('title') + '" alt="' + img.attr('alt') + '" height="' + (height - 50) + '" width="' + (width - 50) + '" />';
						$(this).append(imgtag).hide().fadeIn('3000');

						// key navigation, left, right, ESC
						$(document).one('keyup', function(e) {
							keyCode = e.keyCode;
							switch(keyCode) {
								case 37:
									$('button:eq(1)', $('div.ui-dialog-buttonpane', uigallery.dialog('widget'))).click();
									break;
								case 39:
									$('button:eq(0)', $('div.ui-dialog-buttonpane', uigallery.dialog('widget'))).click();
									break;
								case 27:
									uigallery.dialog('close');
									break;
								default:
									break;
							}
						});

					},
					close: function() {
						removeDialog($(this));
					}
				});
			});

			// Wrapper dialog with related theme
			theme = function(container) {
				var wrapper = '<div class="' + settings.theme + '" style="position: absolute; top: 0; left: 0;"></div>';
				container.parent('.ui-dialog').wrap(wrapper);
				container.dialog('option', 'position', { at : 'center', my : 'center' });
			};

			// Destroy dialog
			removeDialog = function(container) {
				container.closest('.' + settings.theme + '').remove();
				$('#uigallery').remove();
			};

		});
	};

})(jQuery);