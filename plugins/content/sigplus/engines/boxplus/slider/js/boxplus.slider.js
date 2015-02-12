/*!
* @file
* @brief    boxplus image slider engine
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/boxplus
*/

/*
* boxplus: a lightweight pop-up window engine shipped with sigplus
* Copyright 2009-2010 Levente Hunyadi
*
* boxplus is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* boxplus is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with boxplus.  If not, see <http://www.gnu.org/licenses/>.
*/

if (typeof(__jQuery__) == 'undefined') {
	var __jQuery__ = jQuery;
}
(function ($) {
	var CLASS_DISABLED = 'boxplus-disabled';

	/**
	* Maximum computed width of matched elements including margin, border and padding.
	*/
	$.fn.maxOuterWidth = function() {
		var w = 0;
		this.each( function(index, el) {
			w = Math.max(w, $(el).outerWidth(true));
		});
		return w;
	}

	/**
	* Maximum computed height of matched elements including margin, border and padding.
	*/
	$.fn.maxOuterHeight = function() {
		var h = 0;
		this.each( function(index, el) {
			h = Math.max(h, $(el).outerHeight(true));
		});
		return h;
	}

	/**
	* Creates a new image slider from a collection of images.
	* The method should be called on a ul or ol element that wraps a set of li elements.
	*/
	$.fn.boxplusSlider = function (settings) {
		// default configuration properties
		var defaults = {
			rowCount: 1,                // number of rows per slider page
			columnCount: 2,             // number of columns per slider page
			orientation: 'horizontal',  // orientation of sliding image ribbon ['horizontal'|'vertical']
			navigation: 'top',          // position where navigation controls are displayed ['top'|'bottom'|'both']
			showButtons: true,          // whether to show navigation buttons [true|false]
			showLinks: true,            // whether to show navigation links [true|false]
			showPageCounter: true,      // whether to show page counter [true|false]
			showOverlayButtons: true,   // whether to show navigation buttons that overlay image thumbnails [true|false]
			contextmenu: true,          // whether the context menu appears when right-clicking an image [true|false]
			duration: 800,              // duration for scroll animation in milliseconds, or one of ['slow'|'fast']
			delay: 0,                   // time between successive automatic slide steps in milliseconds, or 0 to use no automatic sliding
			opacity: 1                  // item opacity when mouse moves away from item, 1.0 (default) for no opacity visual effect
		};
		settings = $.extend(defaults, settings);

		var lists = this.filter('ul, ol');  // filter elements that are not lists

		// iterate over elements if invoked on an element collection
		lists.each(function () {
			// short-hand access to settings
			var pageRows = settings.rowCount;
			var pageCols = settings.columnCount;
			var pageCount = pageRows * pageCols;  // number of items per page
			var duration = settings.duration;
			var delay = settings.delay;

			// index of item in slider taking the current first position
			var sliderIndexPosition = 0;

			// DOM elements
			var ribbon = $(this).wrap('<div />');
			var gallery = ribbon.parent().addClass('boxplus-slider').addClass(CLASS_DISABLED);
			gallery.wrapInner('<div class="boxplus-viewport" />');

			// get maximum width and height of image slider items
			var listitems = $('li:visible', ribbon);
			var items = listitems.find('img:first');
			var itemWidth = items.maxOuterWidth();
			var itemHeight = items.maxOuterHeight();

			// make image slider items have uniform sizes
			listitems.css({
				width: itemWidth,
				height: itemHeight
			});
			itemWidth = listitems.maxOuterWidth();  // list items themselves might have margin/border/padding
			itemHeight = listitems.maxOuterHeight();

			// compute dimensions for item ribbon
			function _arrange(rows, cols, rowmajor) {
				for (r = 0; r < rows; r++) {
					for (c = 0; c < cols; c++) {
						listitems.eq(rowmajor ? r * cols + c : c * rows + r).css({
							left: c * itemWidth,
							top: r * itemHeight
						});
					}
				}
			}
			var count = listitems.length;  // total number of items in slider
			var rows, cols, r, c;
			var isVerticallyOriented = settings.orientation == 'vertical';
			if (isVerticallyOriented) {  // vertical
				cols = pageCols;
				rows = Math.ceil(count / pageCols);
				_arrange(rows, cols, true);
			} else {  // horizontal
				cols = Math.ceil(count / pageRows);
				rows = pageRows;
				_arrange(rows, cols);
			}

			// set width and height of image ribbon that accomodates all items
			ribbon.css({
				width: cols * itemWidth,
				height: rows * itemHeight
			});

			// set dimensions for viewport, items currently outside viewport dimensions are not visible
			$('.boxplus-viewport', gallery).css({
				width: pageCols * itemWidth,
				height: pageRows * itemHeight
			});
			gallery.css('width', pageCols * itemWidth);

			// setup outside navigation controls
			function _addLink(cls) {
				return '<a class="boxplus-' + cls + '" href="javascript:void(0)" />';
			}
			function _addButton(cls) {
				return '<div class="boxplus-' + cls + '" />';
			}
			function _addLabel(cls, txt) {
				return '<span class="boxplus-' + cls + '">' + txt + '</span>';
			}
			var navigationBar = '<div class="boxplus-paging">' +
				(settings.showButtons ? _addButton('first') + _addButton('prev') : '') +
				(settings.showLinks ? _addLink('first') + '&nbsp;' + _addLink('prev') + ' ' : '') +
				(settings.showPageCounter ? _addLabel('current', 1) + ' / ' + _addLabel('total', Math.ceil( count / pageCount )) : '') +
				(settings.showLinks ? ' ' + _addLink('next') + '&nbsp;' + _addLink('last') : '') +
				(settings.showButtons ? _addButton('next') + _addButton('last') : '') +
				'</div>';
			switch (settings.navigation) {
				case 'both':
					gallery.prepend(navigationBar).append(navigationBar);
					break;
				case 'top':
					gallery.prepend(navigationBar);
					break;
				default:  // case 'bottom':
					gallery.append(navigationBar);
			}

			// setup overlay navigation controls
			if (settings.showOverlayButtons) {
				$('.boxplus-viewport', gallery).append(
					$(_addButton('prev') + _addButton('next')).addClass(
						(isVerticallyOriented ? itemWidth : itemHeight) < 120 ? 'boxplus-small' : 'boxplus-large'
					).addClass(
						isVerticallyOriented ? 'boxplus-vertical' : 'boxplus-horizontal'
					)
				);
			}

			if (!settings.contextmenu) {
				$(document).bind('contextmenu', function (event) {  // subscribe to right-click event
					return !items.filter(event.target).size();  // prevent right-click on image
				});
			}

			// bind events for navigation controls
			var btnFirst = $('.boxplus-first', gallery).click(scrollFirst);  // variable used in updatePaging
			var btnPrev = $('.boxplus-prev', gallery).click(scrollPrevious);
			var btnNext = $('.boxplus-next', gallery).click(scrollNext);
			var btnLast = $('.boxplus-last', gallery).click(scrollLast);

			// bind asynchronous load event for images that are intended for progressive loading
			listitems.addClass('boxplus-loading').children().addClass(CLASS_DISABLED);
			showImages(listitems.not(getAsynchronousItems(listitems).one('boxplus-load', loadImageAsynchronously).get()));

			// update visibility of navigation controls
			updatePaging();
			gallery.removeClass(CLASS_DISABLED);
			ribbon.css({
				top:0,  // reset position to avoid IE7 centering ribbon in viewport
				left:0,
				visibility: 'visible'  // show image ribbon if it has been hidden to avoid erratic browser layout
			});

			// image mouse hover animation
			if (settings.opacity < 1.0) {
				items.css('opacity', settings.opacity);
				items.hover(
					function () {
						$(this).stop().animate({
							opacity: 1.0
						}, 'slow');
					},
					function () {
						$(this).stop().animate({
							opacity: settings.opacity
						}, 'slow');
					}
				);
			}

			// slider animation
			if (delay > 0) {
				delay = Math.max(delay, duration);
				var intervalID = window.setInterval(cycle, delay);
				gallery.mouseover(function () {
					window.clearInterval(intervalID);
				}).mouseout(function () {
					intervalID = window.setInterval(cycle, delay);
				});
			}

			//
			// Asynchronous image loading
			//

			/**
			* List items whose images are to be loaded asynchronously.
			*/
			function getAsynchronousItems(listitems) {
				return listitems.filter(function () {
					return /\.(gif|jpe?g|png)$/i.test($('img:first', this).attr('longdesc'));
				});
			}

			/**
			* Completes loading images by assigning their src attribute.
			*/
			function showImages(listitems) {
				getAsynchronousItems(listitems).each(function () {
					// assign src attribute and clear marker for asynchronous loading
					var image = $('img:first', this);
					image.attr('src', image.attr('longdesc')).attr('longdesc', '');
				});

				// remove wait indicator
				listitems.removeClass('boxplus-loading').children().removeClass(CLASS_DISABLED);

				// show captions
				showCaptions();
			}

			/**
			* Triggers loading an image asynchronously.
			* A wait indicator is shown while the image is loading.
			*/
			function loadImageAsynchronously(event) {
				var listitem = $(event.target);
				var image = $('img:first', listitem);
				$(new Image()).load(function () {  // set up image preloader
					showImages(listitem);          // show image when preloading is finished
				}).attr('src', image.attr('longdesc'));
			}

			/**
			* Activates captions for images scrolled into view.
			*/
			function showCaptions() {
				listitems.slice(sliderIndexPosition, sliderIndexPosition + pageCount).mouseenter().mouseleave();
			}

			//
			// Callback functions
			//

			function cycle() {
				scroll('cycle');
			}

			function scrollFirst() {
				scroll('first');
			}

			function scrollPrevious() {
				scroll('prev');
			}

			function scrollNext() {
				scroll('next');
			}

			function scrollLast() {
				scroll('last');
			}

			/**
			* Execute image slider animation.
			*/
			function scroll(dir) {
				var t = count % pageCount ? count - count % pageCount : count - pageCount;  // greatest possible index for the first position
				switch (dir) {
					case 'first':
						sliderIndexPosition = 0; break;
					case 'prev':
						sliderIndexPosition = (sliderIndexPosition >= pageCount) ? sliderIndexPosition - pageCount : 0; break;
					case 'next':
						sliderIndexPosition = (sliderIndexPosition < t) ? sliderIndexPosition + pageCount : t; break;
					case 'last':
						sliderIndexPosition = t; break;
					case 'cycle':
						sliderIndexPosition = (sliderIndexPosition >= t) ? 0 : sliderIndexPosition + pageCount; break;
					default:
						return;
				};

				var min = Math.max(0, sliderIndexPosition - pageCount);
				var max = Math.min(count, sliderIndexPosition + 2 * pageCount);
				listitems.slice(0, min).detach();  // remove elements from DOM temporarily to speed up rendering the page during an animation
				listitems.slice(max).detach();

				var target = isVerticallyOriented
					? { top: -(sliderIndexPosition / pageCols * itemHeight) }
					: { left: -(sliderIndexPosition / pageRows * itemWidth) };
				switch (dir) {
					case 'first': case 'last':
						ribbon.css($.extend(target, {opacity:0}));
						updatePaging();
						ribbon.prepend(listitems.slice(0, min)).append(listitems.slice(max));
						ribbon.animate({opacity:1}, duration, 'linear');
						break;
					default:
						updatePaging();
						ribbon.animate(target, duration, 'swing', function () {
							ribbon.prepend(listitems.slice(0, min)).append(listitems.slice(max));
						});
				}
			}

			/**
			* Update which navigation links are enabled.
			*/
			function updatePaging() {
				var t = count % pageCount ? count - count % pageCount : count - pageCount;  // greatest possible index for the first position
				btnPrev.add(btnFirst).toggleClass(CLASS_DISABLED, sliderIndexPosition <= 0);
				btnNext.add(btnLast).toggleClass(CLASS_DISABLED, sliderIndexPosition >= t);
				$('.boxplus-current', gallery).text(sliderIndexPosition / pageCount + 1);

				var min = Math.max(0, sliderIndexPosition - pageCount);
				var max = Math.min(count, sliderIndexPosition + 2 * pageCount);
				listitems.slice(min, max).trigger('boxplus-load');

				showCaptions();
			}
		});

		return this;  // support chaining
	}
})(__jQuery__);