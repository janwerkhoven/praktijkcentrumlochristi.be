/**
* @file
* @brief    sigplus Image Gallery Plus save and restore settings control
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

if (typeof(__jQuery__) == 'undefined') {
	var __jQuery__ = jQuery.noConflict();
}

(function ($) {
	function settings_get(name) {
		return $(':input[name=params\\[' + name + '\\]]');
	}
	
	/**
	* Converts back-end settings into a list of key-value pairs.
	*/
	function settings_backup() {
		var textarea = $(this).siblings('textarea');
		var params = $(':input[name^=params]');
		var data = $.map(params.serializeArray(), function (item) {  // iterate name-value pairs
			var match = /^params\[(.*)\]$/.exec(item.name);
			if (match && match[1] != 'settings') {  // do not back up custom settings as a single string
				return match[1] + '=' + escape(item.value);
			}
			return null;
		}).join('\n');
		var customparams = settings_get('settings');
		if (customparams.size()) {
			data += '\n' + customparams.val();
		}
		textarea.val(data);
	}

	/**
	* Converts a list of key-value pairs into their back-end equivalent.
	*/
	function settings_restore() {
		var mapping = {  // maps inline setting to back-end setting
			maxcount:'thumb_count',
			width:'thumb_width',
			height:'thumb_height',
			crop:'thumb_crop',
			orientation:'slider_orientation',
			navigation:'slider_navigation',
			buttons:'slider_buttons',
			links:'slider_links',
			counter:'slider_counter',
			overlay:'slider_overlay',
			duration:'slider_duration',
			animation:'slider_animation',
			borderstyle:'border_style',
			borderwidth:'border_width',
			bordercolor:'border_color',
			sortcriterion:'sort_criterion',
			sortorder:'sort_order'
		};
		var textarea = $(this).siblings('textarea');
		var params = {};
		$.each(textarea.val().split('\n'), function (index, item) {
			var i = item.indexOf('=');
			if (i < 0) {
				return;
			}
			var name = item.substr(0, i);
			var value = item.substr(i+1);
			var elem1 = settings_get(name);
			var elem2 = settings_get(mapping[name]);
			var elem = elem1.size() ? elem1 : elem2;
			if (elem.size()) {  // parameter exists, set in settings form
				if (elem.is('[type=radio]')) {
					elem.filter('[value=' + value + ']').attr('checked', true);
				} else {
					elem.val(unescape(value));
				}
			} else {  // parameter does not exist, add as custom setting
				params[name] = value;
			}
		});
		var customparams = settings_get('settings');
		if (customparams.size()) {
			var data = '';
			for (var name in params) {
				data += name+'='+params[name]+'\n';
			}
			customparams.val(data);
		}
	}
	
	$(function () {
		$('.settings-backup:button').click(settings_backup);
		$('.settings-restore:button').click(settings_restore);
	});
})(__jQuery__);