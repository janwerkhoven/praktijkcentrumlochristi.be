/*!
* @file
* @brief    boxplus: a lightweight pop-up window engine shipped with sigplus
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
	// Settings
	var defaults = {
		theme: false,                  // theme to select when pop-up window opens; if set, style sheets that have a title attribute with a different ending will be disabled
		autofit: true,                 // whether to reduce oversized images to fit browser window when they are displayed [true|false]
		duration: 'slow',              // duration of animation sequences in milliseconds, or one of ['slow'|'fast']
		easing: 'swing',               // speed at which the animation progresses at different points within the change image/content animation ['swing'|'linear']
		loop: false,                   // whether the image/content sequence loops such that the first image/content follows the last [true|false]
		contextmenu: true,             // whether the context menu appears when right-clicking an image inside the pop-up window [true|false]
		title: _getTitle,              // a single-argument function that returns the title text that belongs an anchor passed as a jQuery object
		description: _getDescription,  // a single-argument function that returns the description text that belongs to an anchor passed as a jQuery object
		download: _getDownloadUrl,     // a single-argument function that returns the download URL that belongs to an anchor passed as a jQuery object
		metadata: _getMetadata         // a single-argument function that returns arbirary HTML data that belongs to an anchor passed as a jQuery object
	},
	settings,

	// DOM elements
	background,          // partially shaded background
	dialog,              // lightweight pop-up window dialog
	dialogClone,         // a dialog clone for sizing
	dialogInitWidth,     // initial width for dialog, extracted from CSS
	dialogInitHeight,    // initial height for dialog, extracted from CSS
	dialogDefWidth,      // default width for dialog when there is no image to display, extracted from CSS
	dialogDefHeight,     // default height for dialog when there is no image to display, extracted from CSS
	viewer,              // image/content viewer inside dialog
	viewerimage,         // image in image/content viewer
	viewercontent,       // metadata/content in image/content viewer
	viewervideo,
	viewerflash,
	vieweritems,
	btnPrev,             // navigate to previous image/content button in dialog
	btnNext,             // navigate to next image/content button in dialog
	btnClose,            // close window button in dialog
	btnDownload,         // download image/content button in dialog
	btnMetadata,         // metadata information button in dialog
	resizer,             // resize control
	shrink,
	thumbsBar,           // thumbnail bar for faster navigation
	thumbs,              // list of images on thumbnail bar
	btnScrollBack,       // scroll thumbnail bar backward control
	btnScrollForward,    // scroll thumbnail bar forward control
	caption,             // caption title and text below image/content
	panels,
	doc = $(document),
	body,

	// Image visualization
	preloader,           // image preloader or video dimensions placeholder
	anchors,             // array of HTML anchors to show in gallery
	current = -1,        // index of currently shown image/content in gallery
	progress = 0,        // progress indicator stage (used by _toggleProgress)

	// Constants
	BOXPLUS = 'boxplus',
	CLASS_HIDDEN = BOXPLUS + '-hidden',
	CLASS_UNAVAILABLE = BOXPLUS + '-unavailable',  // indicates that the control is not available in the current context
	CLASS_DISABLED = BOXPLUS + '-disabled';        // indicates that the control is disabled

	//
	// jQuery extensions
	//

	/**
	* Get the current computed outer width for the first element in the set of matched elements provided that the element is visible.
	* @return Width including padding, border and margin if the element is visible, 0 otherwise.
	*/
	$.fn.trueWidth = function () {
		return this.filter(':visible').size() ? this.outerWidth(true) : 0;
	}

	/**
	* Get the current computed outer height for the first element in the set of matched elements provided that the element is visible.
	* @return Height including padding, border and margin if the element is visible, 0 otherwise.
	*/
	$.fn.trueHeight = function () {
		return this.filter(':visible').size() ? this.outerHeight(true) : 0;
	}

	/**
	* "Safe" dimension of an HTML element.
	* Some browsers give invalid values with obj.width() but others give the meaningless,
	* value "auto" with obj.css('width'); this function bridges the differences.
	*/
	function _safeDimension(obj, dim) {
		var cssvalue = parseInt(obj.css(dim));
		return isNaN(cssvalue) ? obj[dim]() : cssvalue;
	}

	function _safeWidth(obj) {
		return _safeDimension(obj, 'width');
	}

	function _safeHeight(obj) {
		return _safeDimension(obj, 'height');
	}

	/**
	* Margin of an HTML element.
	* @param elem An HTML element as a jQuery object.
	* @param side One of 'top', 'left', 'bottom' or 'right'.
	*/
	function _getMargin(elem, side) {
		return parseInt(elem.css('margin-' + side)) || 0;  // enforce integer value, even if margin is auto or NaN
	}

	/**
	* Window dimension, excluding scrollbars.
	* @param dim One of 'width' or 'height'.
	*/
	function _getWindowDimension(dim) {
		var
			bvalue = body['client'+dim],                      // has a (maybe incorrect) nonzero value for all major browsers
			dvalue = document.documentElement['client'+dim],  // might have a (correct) value
			wvalue = window['inner'+dim];                     // might have a (correct) value
		bvalue = bvalue ? bvalue : Infinity;
		dvalue = dvalue ? dvalue : Infinity;
		wvalue = wvalue ? wvalue : Infinity;
		return Math.min(bvalue, dvalue, wvalue);
	}

	/**
	* Window width, excluding scrollbars.
	*/
	function _getWindowWidth() {
		return _getWindowDimension('Width');
	}

	/**
	* Window height, excluding scrollbars.
	*/
	function _getWindowHeight() {
		return _getWindowDimension('Height');
	}

	/**
	* Anchor query parameters.
	* @param anchor An HTML anchor element as a jQuery object.
	* @return An object of anchor query parameters corresponding to key-value pairs in query string.
	*/
	function _getAnchorParams(anchor) {
		var
			ret = {},
			seg = anchor[0].search.replace(/^\?/,'').split('&');
		for (var i = 0; i < seg.length; i++) {
			if (seg[i]) {
				var s = seg[i].split('=');
				ret[s[0]] = s[1];
			}
		}
		return ret;
	}

	/**
	* Image source, inspecting both src and longdesc attribute.
	*/
	function _getImageSource(image) {
		var
			src = image.attr('src'),
			longdesc = image.attr('longdesc');
		return src ? src : ( /\.(gif|jpe?g|png)$/i.test(longdesc) ? longdesc : false );
	}

	//
	// Initialization
	//

	/**
	* Short-hand selector for DOM elements that belong to the boxplus namespace.
	*/
	function _selector(name, ancestor) {
		return $('.' + BOXPLUS + '-' + name, ancestor);
	}

	/**
	* Appends the pop-up window HTML code to the document and initializes global variables.
	* The HTML code inserted is
		<div id="boxplus">
			<div class="boxplus-background boxplus-hidden"></div>
			<div class="boxplus-dialog boxplus-hidden">
				<div class="main">
					<div class="boxplus-viewer boxplus-hidden">
						<div></div>
						<img />
						<video autoplay controls />
						<div class="boxplus-flash"><object><param name="movie" /><embed type="application/x-shockwave-flash" /></object></div>
						<div class="boxplus-prev"></div>
						<div class="boxplus-next"></div>
						<div class="boxplus-resizer">
							<div class="boxplus-enlarge">
							<div class="boxplus-shrink boxplus-disabled">
						</div>
						<div class="boxplus-thumbs">
							<ul></ul>
							<div class="boxplus-rewind"></div>
							<div class="boxplus-forward"></div>
						</div>
						<div class="boxplus-progress"></div>
					</div>
					<div class="boxplus-bottom">
						<div class="boxplus-caption">
							<div class="boxplus-title"></div>
							<div class="boxplus-text"></div>
						</div>
						<div class="boxplus-controls">
							<div class="boxplus-prev"></div>
							<div class="boxplus-next"></div>
							<div class="boxplus-close"></div>
							<div class="boxplus-download"></div>
							<div class="boxplus-metadata"></div>
						</div>
					</div>
				<div>
				<div class="boxplus-sideways boxplus-disabled">
					<div class="boxplus-caption">
						<div class="boxplus-title"></div>
						<div class="boxplus-text"></div>
					</div>
					<div class="boxplus-controls">
						<div class="boxplus-prev"></div>
						<div class="boxplus-next"></div>
						<div class="boxplus-close"></div>
						<div class="boxplus-download"></div>
						<div class="boxplus-metadata"></div>
					</div>
				</div>
				<div class="boxplus-lt"></div>
				<div class="boxplus-t"></div>
				<div class="boxplus-rt"></div>
				<div class="boxplus-l"></div>
				<div class="boxplus-m boxplus-hidden"></div>
				<div class="boxplus-r"></div>
				<div class="boxplus-lb"></div>
				<div class="boxplus-b"></div>
				<div class="boxplus-rb"></div>
				<div class="boxplus-progress"></div>
			</div>
		</div>
	*/
	$(function () {  // fired when DOM tree is finished loading
		function _element(name, contents) {
			return '<div' + (name ? ' class="' + BOXPLUS + '-' + ($.isArray(name) ? name.join(' ') : name) + '"' : '') + '>' + (contents ? contents : '') + '</div>';
		}

		// document body
		body = $('body');

		// HTML for navigation controls
		var navhtml = _element('prev') + _element('next');
		var captionhtml = _element( 'caption', _element('title') + _element('text') );
		var controlshtml = _element( 'controls', navhtml + _element('close') + _element('download') + _element('metadata') );

		// add elements to HTML DOM tree
		var boxplus = $('<div id="' + BOXPLUS + '">' +
			_element(['background', CLASS_HIDDEN]) +
			_element(['dialog', CLASS_HIDDEN],
				_element('main',
					_element(['viewer', CLASS_HIDDEN],
						_element(['content', CLASS_HIDDEN]) +  // must be first element of parent
						'<img />' +
						'<video controls />' +
						_element('flash') +
						navhtml +
						_element('resizer',
							_element('enlarge') +
							_element(['shrink', CLASS_HIDDEN])
						) +
						_element('thumbs',
							'<ul />' + _element('rewind') + _element('forward')
						) +
						_element('progress')  // must be last element of parent
					) +
					_element('bottom', captionhtml + controlshtml)
				) +
				_element(['sideways', CLASS_DISABLED],
					controlshtml + captionhtml
				) +
				_element('lt') + _element('t') + _element('rt') +
				_element('l') + _element('m') + _element('r') +
				_element('lb') + _element('b') + _element('rb') +
				_element('progress')  // must be last element of parent
			) +
		'</div>').appendTo(body);

		// background
		background = _selector('background', boxplus).click(hideDialog)

		// dialog
		dialog = _selector('dialog', boxplus);
		caption = _selector('caption', dialog);    // one or multiple captions
		panels = _selector('bottom', dialog).add(_selector('sideways', dialog));

		// image/content viewer
		viewer = _selector('viewer', dialog);
		viewercontent = $('div:first', viewer);
		viewerimage = $('img:first', viewer);
		viewervideo = $('video', viewer);
		viewerflash = $('div.' + BOXPLUS + '-flash', viewer);
		vieweritems = $([viewerimage[0], viewerflash[0]]).add(viewervideo);
		
		resizer = _selector('resizer', viewer).click(resizeImage);
		shrink = _selector('shrink', resizer);

		// thumbnail bar events
		btnScrollBack = _selector('rewind', viewer).hover(_rewindThumbnailScroll, _stopThumbnailScroll);
		btnScrollForward = _selector('forward', viewer).hover(_forwardThumbnailScroll, _stopThumbnailScroll);
		thumbsBar = _selector('thumbs', viewer);
		thumbs = $('ul', thumbsBar);

		// subscribe to click event for navigation controls
		btnPrev = _selector('prev', dialog).click(previousItem);  // one or multiple controls
		btnNext = _selector('next', dialog).click(nextItem);
		btnClose = _selector('close', dialog).click(hideDialog);
		btnDownload = _selector('download', dialog).click(downloadItem);
		btnMetadata = _selector('metadata', dialog).click(metadataItem);

		// default dialog dimensions to use when there is no image to show
		dialogDefWidth = _safeWidth(dialog);
		dialogDefHeight = _safeHeight(dialog);

		// initial dialog dimensions to use when the pop-up window is opened
		viewer.css({height:0,width:0});  // constrained by minimum viewer width and height
		dialogInitWidth = _safeWidth(dialog);
		dialogInitHeight = _safeHeight(dialog);

		// clone dialog for sizing
		dialogClone = dialog.clone().appendTo(boxplus);
	});

	//
	// Constructors
	//

	/**
	* Binds the lightweight window to appear when image/content links are clicked.
	*/
	$.fn.boxplus = function (settings) {
		return this.boxplusConfigure(settings).click(function (event) {
			showDialog(event.currentTarget);
			return false;  // stop event propagation if registered as event handler
		});
	}

	/**
	* Binds the lightweight window to appear when image links in a gallery are clicked.
	* A gallery should be specified as a list (ul or ol with li as direct children), each of whose elements wraps an individual image.
	*/
	$.fn.boxplusGallery = function (settings) {
		_findGalleryItems(this).boxplus(settings);  // bind to first anchor in each list item
		return this;
	};

	$.fn.boxplusDialog = function (settings) {
		showDialog(this.boxplusConfigure(settings)[0]);  // use DOM node as argument
	}

	/**
	* Configures appearance and behavior for the lightweight pop-up window.
	* @param settings A settings object.
	*/
	$.fn.boxplusConfigure = function (settings) {
		this.data(BOXPLUS, $.extend({}, settings));
		return this;
	}

	/**
	* Configures appearance and behavior for the lightweight pop-up window for all items in a gallery.
	* @param settings A settings object.
	*/
	$.fn.boxplusGalleryConfigure = function (settings) {
		_findGalleryItems(this).boxplusConfigure(settings);
		return this;
	}

	//
	// Hook functions
	//

	/**
	* Extracts the title that belongs to the item currently shown.
	* @param anchor An HTML anchor represented as a jQuery object.
	* @return HTML code to use as image/content caption title.
	*/
	function _getTitle(anchor) {
		var image = $('img:first', anchor);
		return image.size() ? image.attr('alt') : '';
	}

	/**
	* Extracts the description that belongs to the item currently shown.
	* The description will serve as image/content caption text for the item.
	* @param anchor An HTML anchor represented as a jQuery object.
	* @return HTML code to use as caption text.
	*/
	function _getDescription(anchor) {
		return anchor.attr('title');
	}

	/**
	* Extracts the download URL that belongs to the item currently shown.
	* @param anchor An HTML anchor represented as a jQuery object.
	* @return A valid URL, or false.
	*/
	function _getDownloadUrl(anchor) {
		return false;
	}

	/**
	* Extracts the metadata that belongs to the item currently shown.
	* @param anchor An HTML anchor represented as a jQuery object.
	* @return An HTML DOM subtree root as a jQuery object, or an empty string.
	*/
	function _getMetadata(anchor) {
		return '';
	}

	//
	// Public methods
	//

	/**
	* Shows the lightweight pop-up window.
	* @param link The link that has activated the pop-up window.
	*/
	function showDialog(link) {
		var anchor = $(link);
		settings = $.extend({}, defaults, anchor.data(BOXPLUS));  // settings can be cached at global level, only one dialog may be open at once

		// enable associated theme (if any) and disable other themes that might be linked to the page
		var theme = settings.theme;
		if (theme) {
			var disabled = 'disabled';
			$('link[rel=stylesheet][title^=' + BOXPLUS + ']').attr(disabled, disabled).filter('[title="' + BOXPLUS + '-' + theme + '"]').attr(disabled, null);  // disable unused themes and enable selected theme
		}

		// show shaded background
		background.removeClass(CLASS_HIDDEN);

		// position the pop-up window in the center
		_selector('m', dialog).addClass(CLASS_HIDDEN);
		dialog.removeClass(CLASS_DISABLED).css({
			width: dialogInitWidth,
			height: dialogInitHeight
		}).css(_center(dialogInitWidth, dialogInitHeight)).removeClass(CLASS_HIDDEN);
		_toggleProgress(dialog, true);

		// register events
		doc.bind('keydown', _onKeyDown);
		if (!settings.contextmenu) {
			doc.bind('contextmenu', _onContextMenuOpen);  // subscribe to right-click event
		}

		// query URL of images in gallery
		var rel = anchor.attr('rel');
		anchors = rel ? $('a[rel="' + rel + '"]') : anchor;  // related anchors (if any)

		// add thumbnails
		thumbs.empty();
		anchors.each(function (index) {
			var image = $('img:first', this);
			$('<li />').append((image.size() ? $('<img />').attr('src', _getImageSource(image)) : $()).click(function () {
				changeItem(index);
			})).appendTo(thumbs);
		});
		thumbsBar.toggleClass(CLASS_UNAVAILABLE, thumbs.children().size() < 2).css({
			height: thumbs.trueHeight()  // sets ribbon height based on maximum height of images (incl. margin, border and padding)
		});

		// load first image
		changeItem($.inArray(link, anchors.get()));  // find the index of the anchor that has been clicked from among those in the link set
	}

	/**
	* Hides the lightweight pop-up window.
	* Fired when the user clicks the close button, clicks outside the pop-up window or presses the ESC key.
	*/
	function hideDialog() {
		// unregister events
		doc.unbind('keydown', _onKeyDown).unbind('contextmenu', _onContextMenuOpen);
		dialog.stop(true, true);

		current = -1;  // no image is displayed

		dialog.addClass(CLASS_DISABLED).add(panels).add(viewer).add(background).addClass(CLASS_HIDDEN);
	}

	/**
	* Navigate to the first image/content.
	* Fired when the user clicks the navigate to first control or presses the HOME key.
	*/
	function firstItem() {
		changeItem(0);
	}

	/**
	* Navigate to the previous image/content.
	* Fired when the user clicks the navigate to previous control or presses the left arrow key.
	*/
	function previousItem() {
		changeItem(current - 1);
	}

	/**
	* Navigate to the next image/content.
	* Fired when the user clicks the navigate to next control or presses the right arrow key.
	*/
	function nextItem() {
		changeItem(current + 1);
	}

	/**
	* Navigate to the last image/content.
	* Fired when the user clicks the navigate to last control or presses the END key.
	*/
	function lastItem() {
		changeItem(anchors.length-1);
	}

	/**
	* Navigate to the image/content with the specified index.
	* Fired when the user clicks a thumbnail in the scrollable thumbnail bar.
	*/
	function changeItem(index) {
		var count = anchors.length;
		if (index != current && (settings.loop || index >= 0 && index < count)) {
			current = (index + count) % count;  // avoid mod operator with negative numbers
			_setShrunk(true);
			_changeItem();
		}
	}

	/**
	* Enlarges or shrinks an image, as appropriate.
	* Fired when the user clicks the enlarge/shrink control in the upper right corner of the window.
	*/
	function resizeImage() {
		_setShrunk(!_isShrunk());
		_changeItem();
	}

	/**
	* Prepares an item for display in the viewer possibly using a preloaded image.
	*/
	function refreshItem() {
		// hide image viewer and show progress indicator
		viewer.addClass(CLASS_HIDDEN);
		_toggleProgress(viewer, false);
		_toggleProgress(dialog, true);

		// hide caption and disable navigation controls
		panels.addClass(CLASS_HIDDEN);

		// get target dimensions and placement for dialog
		var
			autofit = settings.autofit,
			dimensions = {},
			dlgdimensions = {},
			position = {};
		_getPlacement(autofit && _isShrunk(), dimensions, dlgdimensions, position);  // resizing is enabled and image can expand

		// set image viewer width, height and image source
		viewer.css(dimensions);
		viewerimage.attr('src', anchors.eq(current).attr('href'));

		// turn on resizer if image has been reduced to fit window
		resizer.toggleClass(CLASS_UNAVAILABLE, !autofit || !preloader || dimensions.width == preloader.width && _isShrunk());

		// resize dialog box with animation (image caption not shown yet)
		_selector('m', dialog).addClass(CLASS_HIDDEN);
		dialog.animate($.extend(position, dlgdimensions), settings.duration, settings.easing, _showItem);
	}

	function downloadItem() {
		window.location.href = settings.download(anchors.eq(current));
	}

	function metadataItem() {
		viewer.children().slice(0,-1).toggleClass(CLASS_HIDDEN);  // do not toggle progress bar, which is the last element
	}

	$.boxplus = {
		/** Displays the specified item in the gallery. */
		change: changeItem,
		/** Displays the previous item in the gallery. */
		previous: previousItem,
		/** Displays the next item in the gallery. */
		next: nextItem,
		refresh: refreshItem,
		/** Changes configuration defaults. */
		configure: function (settings) {
			return $.extend(defaults, settings);  // updates left-hand side object
		}
	};

	//
	// Private methods for image display
	//

	/**
	* Finds anchors that belong to a gallery.
	* @param gallery The root node of a gallery DOM subtree wrapped in a jQuery object.
	* @return A jQuery collection of anchors in a gallery.
	*/
	function _findGalleryItems(gallery) {
		// fetch or construct gallery identifier
		var id = gallery.attr('id');
		id = id ? BOXPLUS + '-' + id : BOXPLUS;

		$('li', gallery).each(function () {
			$('a:first', this).attr('rel', id);
		});
		return $('a[rel=' + id + ']', gallery);
	}

	/**
	* Initiates changing the image/content displayed in the lightweight pop-up window.
	*/
	function _changeItem() {
		dialog.stop(true, true);
		thumbs.stop();

		// hide caption and disable navigation controls
		panels.addClass(CLASS_HIDDEN);

		// show progress indicator
		_toggleProgress(viewer, true);

		// clear metadata
		viewercontent.empty();
		
		// hide image, video and flash viewer
		vieweritems.addClass(CLASS_UNAVAILABLE);

		var anchor = anchors.eq(current);
		var a = anchor[0];
		var
			href = anchor.attr('href'),
			path = a.pathname;
		if (/^#/.test(href)) {  // content in the same document
			viewercontent.append($(href).clone().contents());  // clone DOM subtree
			_prepareText();
		} else if (/\.(txt|html?)$/i.test(path)) {
			_loadContent(href);
		} else if (/\.(gif|jpe?g|png)$/i.test(path)) {  // preload image
			_loadImage(anchor);
		} else if (/\.(mov|mpe?g|ogg|swf|webM|wmv)$/i.test(path) || /youtube\.com$/.test(a.hostname)) {
			_loadVideo(anchor);
		} else {  // content from external source using URL
			$.ajax({
				url: href,
				success: function (response, status, xhdr) {
					var contentType = xhdr.getResponseHeader("Content-Type");  // content type is empty when browsing locally, must use extension instead
					if (/^text\/(plain|html)/.test(contentType)) {
						_loadContent(href);
					} else if (/^image\/(gif|jpeg|png)/.test(contentType)) {
						_loadImage(anchor);
					} else if (/^application\/x-shockwave-flash/.test(contentType)) {
						_loadVideo(anchor);
					}
				},
				type: 'head'
			});
		}
	}

	/**
	* Prepares HTML content for display in the viewer.
	* Content-type is assumed "text/plain" or "text/html".
	* @param href The URL to the content to display.
	*/
	function _loadContent(href) {
		viewercontent.load(href, {}, _prepareText);
	}

	/**
	* Prepares an image to be shown in the viewer.
	* Content-type is assumed one of "image/gif", "image/jpeg" or "image/png".
	* @param anchor An HTML anchor as a jQuery object.
	*/
	function _loadImage(anchor) {
		// set metadata
		viewercontent.append(settings.metadata(anchor));

		// display image when image has been loaded, src must be initialized last for some browsers
		viewerimage.removeClass(CLASS_UNAVAILABLE);
		$(preloader = new Image()).load(refreshItem).error(_prepareText).attr('src', anchor.attr('href'));
	}

	/**
	* Prepares a video to be shown in the viewer.
	* @param anchor An HTML anchor as a jQuery object.
	*/
	function _loadVideo(anchor) {
		var
			href = anchor.attr('href'),
			params = _getAnchorParams(anchor);
		var
			w = parseInt(params.width) || 640,
			h = parseInt(params.height) || 480;
		var dims = {
			width: w,
			height: h
		};
		if (/\.(ogg|webM)$/i.test(anchor[0].pathname)) {
			viewervideo.attr($.extend({
				src: href
			}, dims)).removeClass(CLASS_UNAVAILABLE);
		} else {
			var dimattr = ' width="'+w+'" height="'+h+'"';
			viewerflash.empty().append('<object'+dimattr+'><param name="movie" value="'+href+'"><embed type="application/x-shockwave-flash"'+dimattr+' src="'+href+'"></object>').removeClass(CLASS_UNAVAILABLE);
		}	
		preloader = $.extend({}, dims);
		refreshItem();
	}
	
	/**
	* Prepares HTML content for display in the viewer.
	*/
	function _prepareText() {
		preloader = null;
		refreshItem();
	}

	/**
	* Displays the image in the viewer.
	*/
	function _showItem() {
		_selector('m', dialog).removeClass(CLASS_HIDDEN);
		thumbs.css('left', -$('li', thumbs).eq(current).position().left);
		_updateThumbnailNavigation();

		// show image viewer
		viewer.removeClass(CLASS_HIDDEN);

		// show navigation controls as appropriate
		var loop = settings.loop;
		btnPrev.toggleClass(CLASS_UNAVAILABLE, !loop && current == 0);
		btnNext.toggleClass(CLASS_UNAVAILABLE, !loop && current >= anchors.length-1);

		// reset metadata view state
		viewer.children().removeClass(CLASS_HIDDEN);

		// show action controls as appropriate
		var hasimage = preloader && preloader.src;
		var hascontent = !viewercontent.is(':empty');
		btnDownload.toggleClass(CLASS_UNAVAILABLE, !settings.download(anchors.eq(current)));
		btnMetadata.toggleClass(CLASS_UNAVAILABLE, !hasimage || !hascontent);
		viewercontent.addClass(CLASS_HIDDEN);
		hasimage || !hascontent || metadataItem();  // show metadata if there is no image

		// remove wait indicators
		_toggleProgress(dialog, false);

		// add (or hide) caption text
		_setCaption(caption);

		// resize dialog to show caption text
		var target = {
			width: _safeWidth(dialog) + _selector('sideways', dialog).trueWidth(),
			height: _safeHeight(dialog) + _heightExtension(dialog)
		};
		dialog.animate(target, settings.duration, settings.easing, function () {
			panels.removeClass(CLASS_HIDDEN);  // displays the image caption text
		});
	}

	//
	// Private methods for thumbnail bar display
	//

	/**
	* Updates the visibility of thumbnail navigation controls.
	*/
	function _updateThumbnailNavigation() {
		var
			diff = _safeWidth(viewer) - _safeWidth(thumbs),
			left = thumbs.position().left;
		btnScrollBack.toggleClass(CLASS_HIDDEN, left >= 0);
		btnScrollForward.toggleClass(CLASS_HIDDEN, diff > 0 || left <= diff);
	}

	/**
	* Scrolls the thumbnail ribbon towards the first thumbnail image.
	*/
	function _rewindThumbnailScroll() {
		thumbs.animate({ left: 0 }, -4 * thumbs.position().left, 'linear', _updateThumbnailNavigation);  // "left" is always a negative number
	}

	/**
	* Scrolls the thumbnail ribbon towards the last thumbnail image.
	*/
	function _forwardThumbnailScroll() {
		var
			vw = _safeWidth(viewer),
			tw = _safeWidth(thumbs);
		var left = thumbs.position().left;  // current left offset of thumbs ribbon w.r.t. left edge of viewer
		var minleft = vw - tw;              // maximum negative value permitted as left offset w.r.t. left edge of viewer

		if (tw + left < vw) {  // right end of ribbon is to the right of right edge of viewer
			_updateThumbnailNavigation();
		} else {
			thumbs.animate({ left: minleft }, -4 * (minleft - left), 'linear', _updateThumbnailNavigation);
		}
	}

	/**
	* Fired when the mouse is no longer over one of the thumbnail navigation controls.
	*/
	function _stopThumbnailScroll() {
		thumbs.stop();
		_updateThumbnailNavigation();
	}

	//
	// Private methods for resizer control
	//

	/**
	* Gets whether the image currently displayed is in shrunk state.
	*/
	function _isShrunk() {
		return shrink.hasClass(CLASS_HIDDEN);
	}

	/**
	* Sets whether the image currently displayed is in shrunk state.
	* @param toggle If true, hide enlarge button and show shrink button; if false, vice versa.
	*/
	function _setShrunk(toggle) {
		_selector('enlarge', resizer).toggleClass(CLASS_HIDDEN, !toggle);
		shrink.toggleClass(CLASS_HIDDEN, toggle);
	}

	//
	// Private methods for labels
	//

	/**
	* Sets caption title and text.
	* @param caption A jQuery object to assign the caption to.
	*/
	function _setCaption(caption) {
		var anchor = anchors.eq(current);
		var
			title = settings.title(anchor),
			text = settings.description(anchor);
		_selector('title', caption).toggleClass(CLASS_UNAVAILABLE, !title).html(title);
		_selector('text', caption).toggleClass(CLASS_UNAVAILABLE, !text || text == title).html(text);
	}

	//
	// Private methods for progress indicator
	//

	/**
	* Enables or disabled a progress indicator.
	* A progress indicator is a PNG image with alpha transparency
	*/
	function _toggleProgress(ctrl, on) {
		var indicator = _selector('progress', ctrl).toggleClass(CLASS_HIDDEN, !on);
		window.clearInterval(indicator.data(BOXPLUS));
		if (on) {
			indicator.data(BOXPLUS, window.setInterval(function () {
				indicator.css('background-position', progress = (progress - 32) % 384);  // 384px = 12 states * 32px width
			}, 150));
		}
	}

	//
	// Private methods for dialog positioning
	//

	/**
	* The height the dialog grows when caption and controls are shown.
	*/
	function _heightExtension(dlg) {
		return _selector('caption', dlg).trueHeight() + _selector('controls', dlg).trueHeight();
	}

	/**
	* Returns coordinates to place a rectangle in the middle of the browser window with absolute positioning.
	*/
	function _center(width, height) {
		var
			x = _getWindowWidth() / 2,
			y = _getWindowHeight() / 2;
		var max = Math.max;
		return {
			left: max(0, $(window).scrollLeft() + x - width / 2),  // user should always be able to view the full image by scrolling the document
			top: max(0, $(window).scrollTop() + y - height / 2)
		};
	}

	/**
	* Get target dimensions and placement used for centering the lightweight pop-up window.
	* @param resize Whether to resize the pop-up window to fit to screen.
	*/
	function _getPlacement(resize, dimensions, dlgdimensions, position) {
		// get image dimensions
		$.extend(dimensions, {
			width: preloader ? preloader.width : dialogDefWidth,
			height: preloader ? preloader.height : dialogDefHeight
		});

		// add caption text, which affects centering (and whether dialog fits into browser window)
		_setCaption(_selector('caption', dialogClone.removeClass(CLASS_UNAVAILABLE)));

		// set width and height extension of dialog in second animation phase
		var
			sidewaysClone = _selector('sideways', dialogClone),
			mainClone = _selector('main', dialogClone);
		var w = mainClone.trueWidth() - _safeWidth(mainClone) + sidewaysClone.trueWidth();

		// set image/content viewer width and height in dialog clone
		var viewerClone = _selector('viewer', dialogClone.css('width', dimensions.width + w)).css(dimensions);

		if (resize) {
			var win_w = _getWindowWidth() - _getMargin(body, 'left') - _getMargin(body, 'right');

			// compute image/content viewer width using dialog clone
			var dlg_w = dialogClone.outerWidth(true);
			if (dlg_w > win_w) {
				var ratio = win_w / dlg_w;
				dimensions.width *= ratio;
				dimensions.height *= ratio;
				viewerClone.css(dimensions);  // guaranteed to fit horizontally but may exceed browser window height
				dialogClone.css('width', dimensions.width + w);
			}
		}

		var dlg_h = dialogClone.outerHeight(true);
		if (resize) {
			var win_h = _getWindowHeight() - _getMargin(body, 'top') - _getMargin(body, 'bottom');

			// compute image/content viewer height using dialog clone
			while (dlg_h > win_h) {
				ratio = win_h / dlg_h;
				dimensions.width *= ratio;
				dimensions.height *= ratio;
				viewerClone.css(dimensions);
				dlg_h = dialogClone.css('width', dimensions.width + w).outerHeight(true);  // may still exceed window height if resize caused caption text to re-flow
			}
		}
		dlgdimensions.width = _safeWidth(dialogClone) - sidewaysClone.trueWidth();
		dlgdimensions.height = _safeHeight(dialogClone) - _heightExtension(dialogClone);
		$.extend(position, _center(dimensions.width + w, dlg_h));  // use viewer height extended with caption text height when calculating vertical middle
		dialogClone.addClass(CLASS_UNAVAILABLE);
	}

	//
	// Events
	//

	/**
	* Fired when the user presses a key while the lightweight pop-up window is shown.
	* This event is associated with the document element.
	*/
	function _onKeyDown(event) {
		if (event.target.tagName.toLowerCase() != 'input') {  // let form elements handle their own input
			var keyindex = $.inArray(event.which, [27,37,39,36,35]);  // keys are [ESC, left arrow, right arrow, home, end]
			keyindex < 0 || [hideDialog,previousItem,nextItem,firstItem,lastItem][keyindex]();
			return false;  // cancel event propagation
		}
	}

	/**
	* Fired when the user right-clicks on an element
	* This event is associated with the document element.
	*/
	function _onContextMenuOpen(event) {
		return !$('img', thumbs).add(viewerimage).filter(event.target).size();  // prevent right-click on image
	}
})(__jQuery__);