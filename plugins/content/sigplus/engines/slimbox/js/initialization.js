/**
* @file
* @brief    sigplus Image Gallery Plus initialization for Slimbox
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

function bindSlimbox(gallery, options) {
	$$($ES('a', gallery).filter(function(el) {
		return el.rel && el.rel.test(/^slimbox($|-)/i);
	})).slimbox(
		// options
		options,
		// link mapper
		function(el) {
			var image = el.getElement('img');
			var url = el.getProperty('href');
			var summary = document.getElementById(image.getProperty('id') + '_summary');
			if (summary) {
				return [url, summary.innerHTML];
			} else if (image) {
				return [url, image.getProperty('title')];
			} else {
				return [url, el.getProperty('title')]
			}
		},
		// link filter
		function(el) {
			return (this == el) || ((this.rel.length > 'slimbox'.length) && (this.rel == el.rel));
		}
	);
}