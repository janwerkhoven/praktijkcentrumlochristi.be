/*!
* @file
* @brief    sigplus Image Gallery Plus gallery external linkage
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

if (typeof(__jQuery__) == 'undefined') {
	var __jQuery__ = jQuery;
}
(function ($) {
	$.fn.sigplusLinkage = function (items, rel, deftitle, defdescription) {
		var gallery = this;
		var galleryid = gallery.attr('id');
		var list = $('ul:first', this);
		
		$.each(items, function (index, item) {
			var url = item[0];
			var previewurl = item[1];
			var width = item[2];
			var height = item[3];
			var thumburl = item[4];
			var title = item[5] ? item[5] : deftitle;
			var description = item[6] ? item[6] : defdescription;
			var downloadurl = item[7];
			var iptc = item[8];
			
			// preview image (possibly wrapped in anchor)
			var imageid = galleryid + '_img' + ('000' + index).substr(-4);
			var image = $('<img />').attr({
				id: imageid,
				width: width,
				height: height,
				alt: title
			});
			if (thumburl) {
				image.attr({
					src: thumburl,
					longdesc: previewurl
				});
			} else {
				image.attr({
					src: previewurl
				});
			}
			if (url) {
				var anchor = $('<a />').attr({
					href: url,
					rel: rel,
					title: description
				}).append(image);
			} else {
				var anchor = image;  // no anchor
			}
			
			// image metadata
			var metadata = $('<div style="display:none !important;" />').attr('id', imageid + '_metadata');
			if (description) {
				$('<div>' + description + '</div>').attr('id', imageid + '_summary').appendTo(metadata);
			}
			if (downloadurl) {
				$('<a rel="download" />').attr('href', downloadurl).appendTo(metadata);
			}
			if (iptc) {
				var metatable = $('<table />');
				for (var key in iptc) {
					var value = iptc[key];
					
					var row = $('<tr />').appendTo(metatable);
					$('<th />').appendTo(row).text(key);
					
					var str = $.isArray(value) ? value.join(', ') : value;
					$('<td />').appendTo(row).text(str);
				}
				$('<div />').attr('id', imageid + '_iptc').append(metatable).appendTo(metadata);
			}

			$('<li />').append(anchor).append(metadata.children().size() ? metadata : $()).appendTo(list);
		});
	}
})(__jQuery__);