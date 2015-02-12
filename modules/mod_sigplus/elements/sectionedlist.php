<?php
/**
* @file
* @brief    sigplus Image Gallery Plus custom list control with grouped items
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

/*
* sigplus Image Gallery Plus plug-in for Joomla
* Copyright 2009-2010 Levente Hunyadi
*
* sigplus is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* sigplus is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

// This file is fully compatible with PHP 4.

/**
* An item in a group that may contain other items.
* Provides HTML rendering capabilities into a list of @c option and @c optgroup elements in a @c select element.
*/
class JElementSection {
	/*private*/ var $value;
	/*private*/ var $label;
	/*private*/ var $items;

	function JElementSection($value = false, $label = '') {
		$this->value = $value;
		$this->label = $label;
		$this->items = array();
	}
	
	/**
	* Generates an HTML @c option list with option groups (@c optgroup).
	* @param selected The possibly nested key that is selected.
	* @return HTML for the option list.
	*/
	/*public*/ function render($selected, $prefix = false) {
		$t = JFilterOutput::ampReplace($this->label);  // ensure ampersands are encoded
		$k = $prefix !== false ? $prefix.'/'.$this->value : $this->value;
		
		if (count($this->items) > 0) {  // an option group
			$html = '';
			if ($k !== false) {  // not the root group
				$html .= '<optgroup label="'.JText::_($t).'">';
			}
			foreach ($this->items as $option) {
				$html .= $option->render($selected, $k);
			}
			if ($k !== false) {  // not the root group
				$html .= '</optgroup>';
			}
			return $html;
		} else {  // a single option
			if ($k !== false) {
				$s = ($selected === $k ? ' selected="selected"' : '');
				return '<option value="'.$k.'"'.$s.'>'.JText::_($t).'</option>';
			} else {  // no options in root group
				return '';
			}
		}
	}

	/**
	* Adds a nested item to a group.
	* @param keys The path to the newly added item as an array. Each array element is a key for a single level.
	* @param label The label to use for the newly added item.
	*/
	/*private*/ function add_item(&$keys, $label) {
		$k = array_shift($keys);  // get first key
		if (!isset($this->items[$k])) {
			$this->items[$k] = new JElementSection($k);
		}
		$i =& $this->items[$k];
		if (count($keys) >= 1) {  // node
			$i->add_item($keys, $label);
		} else {  // leaf
			$i->label = $label;
		}
	}
	
	/**
	* Adds a nested item to a group based on a key.
	* @param key The path to the newly added item as a string. Path elements are separated by a '/'.
	* @param label The label to use for the newly added item.
	*/
	/*public*/ function add($key, $label) {
		$keys = explode('/', $key);
		$this->add_item($keys, $label);
	}
}

/**
* Renders a list control with grouped items.
* This class represents a user-defined control in the administration backend.
*/
class JElementSectionedList extends JElement {
	/**
	* Element type.
	*/
	var $_name = 'SectionedList';

	/**
	* Generates an HTML @c select list with option groups.
	* @param name The value of the HTML name attribute.
	* @param attribs Additional HTML attributes for the <select> tag.
	* @param selected The possibly nested key that is selected.
	* @return HTML for the select list.
	*/
	/*private*/ function renderHtmlSelect($options, $name, $attribs = null, $selected = null, $idtag = false) {
		if (is_array($attribs)) {
			$attribs = JArrayHelper::toString($attribs);
		}

		$id = $name;
		if ( $idtag ) {
			$id = $idtag;
		}
		$id = str_replace('[','',$id);
		$id	= str_replace(']','',$id);

		$html = '<select name="'. $name .'" id="'. $id .'" '. $attribs .'>';
		$html .= $options->render($selected);
		$html .= '</select>';
		return $html;
	}	
	
	/*public*/ function fetchElement($name, $value, &$node, $control_name) {
		$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="inputbox"' );

		$options = new JElementSection();  // root group
		foreach ($node->children() as $o) {
			$val = $o->attributes('value');
			$text = $o->data();
			$options->add($val, $text);
		}

		return $this->renderHtmlSelect($options, $control_name.'['.$name.']', $class, $value, $control_name.$name);
	}
}