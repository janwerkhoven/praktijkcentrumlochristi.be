<?php
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

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Pagination Class.  Provides a common interface for content pagination for the
 * Joomla! Framework
 *
 * @package 	Joomla.Framework
 * @subpackage	HTML
 * @since		1.5
 */
class CdWebGalleryJPagination extends JObject
{
	/**
	 * URL argument, &page_1, &page_2...
	 * 
	 * @access public
	 * @var string
	 */
	var $uriPart = '';
	
	/**
	 * The record number to start dislpaying from
	 *
	 * @access public
	 * @var int
	 */
	var $limitstart = null;

	/**
	 * Number of rows to display per page
	 *
	 * @access public
	 * @var int
	 */
	var $limit = null;

	/**
	 * Total number of rows
	 *
	 * @access public
	 * @var int
	 */
	var $total = null;

	/**
	 * View all flag
	 *
	 * @access protected
	 * @var boolean
	 */
	var $_viewall = false;

	/**
	 * Constructor
	 *
	 * @param	int		The total number of items
	 * @param	int		The offset of the item to start at
	 * @param	int		The number of items to display per page
	 */
	function __construct($total, $limitstart, $limit, $uriPart)
	{
		// Value/Type checking
		$this->total		= (int) $total;
		$this->limitstart	= (int) max($limitstart, 0);
		$this->limit		= (int) max($limit, 0);
		
		$this->uriPart = '&' . $uriPart;

		if ($this->limit > $this->total) {
			$this->limitstart = 0;
		}

		if (!$this->limit)
		{
			$this->limit = $total;
			$this->limitstart = 0;
		}

		if ($this->limitstart > $this->total) {
			$this->limitstart -= $this->limitstart % $this->limit;
		}

		// Set the total pages and current page values
		if($this->limit > 0)
		{
			$this->set( 'pages.total', ceil($this->total / $this->limit));
			$this->set( 'pages.current', ceil(($this->limitstart + 1) / $this->limit));
		}

		// Set the pagination iteration loop values
		$displayedPages	= 10;
		$this->set( 'pages.start', (floor(($this->get('pages.current') -1) / $displayedPages)) * $displayedPages +1);
		if ($this->get('pages.start') + $displayedPages -1 < $this->get('pages.total')) {
			$this->set( 'pages.stop', $this->get('pages.start') + $displayedPages -1);
		} else {
			$this->set( 'pages.stop', $this->get('pages.total'));
		}

		// If we are viewing all records set the view all flag to true
		if ($this->limit == $total) {
			$this->_viewall = true;
		}
	}
	
	/**
	 * Create and return the pagination result set counter string, ie. Results 1-10 of 42
	 *
	 * @access	public
	 * @return	string	Pagination result set counter string
	 * @since	1.5
	 */
	function getResultsCounter()
	{
		// Initialize variables
		$html = null;
		$fromResult = $this->limitstart + 1;

		// If the limit is reached before the end of the list
		if ($this->limitstart + $this->limit < $this->total) {
			$toResult = $this->limitstart + $this->limit;
		} else {
			$toResult = $this->total;
		}

		// If there are results found
		if ($this->total > 0) {
			$msg = JText::sprintf('Results of', $fromResult, $toResult, $this->total);
			$html .= "\n".$msg;
		} else {
			$html .= "\n".JText::_('No records found');
		}

		return $html;
	}

	/**
	 * Create and return the pagination page list string, ie. Previous, Next, 1 2 3 ... x
	 *
	 * @access	public
	 * @return	string	Pagination page list string
	 * @since	1.0
	 */
	function getPagesLinks()
	{
		global $mainframe;

		$lang =& JFactory::getLanguage();

		// Build the page navigation list
		$data = $this->_buildDataObject();

		$list = array();

		$itemOverride = false;
		$listOverride = false;

		$chromePath = JPATH_THEMES.DS.$mainframe->getTemplate().DS.'html'.DS.'pagination.php';
		if (file_exists($chromePath))
		{
			require_once ($chromePath);
			if (function_exists('pagination_item_active') && function_exists('pagination_item_inactive')) {
				$itemOverride = true;
			}
			if (function_exists('pagination_list_render')) {
				$listOverride = true;
			}
		}

		// Build the select list
		if ($data->all->base !== null) {
			$list['all']['active'] = true;
			$list['all']['data'] = ($itemOverride) ? pagination_item_active($data->all) : $this->_item_active($data->all);
		} else {
			$list['all']['active'] = false;
			$list['all']['data'] = ($itemOverride) ? pagination_item_inactive($data->all) : $this->_item_inactive($data->all);
		}

		if ($data->start->base !== null) {
			$list['start']['active'] = true;
			$list['start']['data'] = ($itemOverride) ? pagination_item_active($data->start) : $this->_item_active($data->start);
		} else {
			$list['start']['active'] = false;
			$list['start']['data'] = ($itemOverride) ? pagination_item_inactive($data->start) : $this->_item_inactive($data->start);
		}
		if ($data->previous->base !== null) {
			$list['previous']['active'] = true;
			$list['previous']['data'] = ($itemOverride) ? pagination_item_active($data->previous) : $this->_item_active($data->previous);
		} else {
			$list['previous']['active'] = false;
			$list['previous']['data'] = ($itemOverride) ? pagination_item_inactive($data->previous) : $this->_item_inactive($data->previous);
		}

		$list['pages'] = array(); //make sure it exists
		foreach ($data->pages as $i => $page)
		{
			if ($page->base !== null) {
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data'] = ($itemOverride) ? pagination_item_active($page) : $this->_item_active($page);
			} else {
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data'] = ($itemOverride) ? pagination_item_inactive($page) : $this->_item_inactive($page);
			}
		}

		if ($data->next->base !== null) {
			$list['next']['active'] = true;
			$list['next']['data'] = ($itemOverride) ? pagination_item_active($data->next) : $this->_item_active($data->next);
		} else {
			$list['next']['active'] = false;
			$list['next']['data'] = ($itemOverride) ? pagination_item_inactive($data->next) : $this->_item_inactive($data->next);
		}
		if ($data->end->base !== null) {
			$list['end']['active'] = true;
			$list['end']['data'] = ($itemOverride) ? pagination_item_active($data->end) : $this->_item_active($data->end);
		} else {
			$list['end']['active'] = false;
			$list['end']['data'] = ($itemOverride) ? pagination_item_inactive($data->end) : $this->_item_inactive($data->end);
		}

		if($this->total > $this->limit){
			return ($listOverride) ? pagination_list_render($list) : $this->_list_render($list);
		}
		else{
			return '';
		}
	}

	function _list_render($list)
	{
		// Initialize variables
		$html = null;

		// Reverse output rendering for right-to-left display
		$html .= '&lt;&lt; ';
		$html .= $list['start']['data'];
		$html .= ' &lt; ';
		$html .= $list['previous']['data'];
		foreach( $list['pages'] as $page ) {
			$html .= ' '.$page['data'];
		}
		$html .= ' '. $list['next']['data'];
		$html .= ' &gt;';
		$html .= ' '. $list['end']['data'];
		$html .= ' &gt;&gt;';

		return $html;
	}

	function _item_active(&$item)
	{
		global $mainframe;
		if ($mainframe->isAdmin())
		{
			if($item->base>0)
				return "<a title=\"".$item->text."\" onclick=\"javascript: document.adminForm.limitstart.value=".$item->base."; submitform();return false;\">".$item->text."</a>";
			else
				return "<a title=\"".$item->text."\" onclick=\"javascript: document.adminForm.limitstart.value=0; submitform();return false;\">".$item->text."</a>";
		} else {
			return "<a title=\"".$item->text."\" href=\"".$item->link."\" class=\"pagenav\">".$item->text."</a>";
		}
	}

	function _item_inactive(&$item)
	{
		global $mainframe;
		if ($mainframe->isAdmin()) {
			return "<span>".$item->text."</span>";
		} else {
			return "<span class=\"pagenav\">".$item->text."</span>";
		}
	}

	/**
	 * Create and return the pagination data object
	 *
	 * @access	public
	 * @return	object	Pagination data object
	 * @since	1.5
	 */
	function _buildDataObject()
	{
		// Initialize variables
		$data = new stdClass();

		$data->all	= new CdWebGalleryJPaginationObject(JText::_('View All'));
		if (!$this->_viewall) {
			$data->all->base	= '0';
			$data->all->link	= JRoute::_($this->uriPart . '=');
		}

		// Set the start and previous data objects
		$data->start	= new CdWebGalleryJPaginationObject(JText::_('Start'));
		$data->previous	= new CdWebGalleryJPaginationObject(JText::_('Prev'));

		if ($this->get('pages.current') > 1)
		{
			$page = ($this->get('pages.current') -2) * $this->limit;

			$page = $page == 0 ? '' : $page; //set the empty for removal from route

			$data->start->base	= '0';
			$data->start->link	= JRoute::_($this->uriPart . '=');
			$data->previous->base	= $page;
			$data->previous->link	= JRoute::_($this->uriPart . '=' . $page);
		}

		// Set the next and end data objects
		$data->next	= new CdWebGalleryJPaginationObject(JText::_('Next'));
		$data->end	= new CdWebGalleryJPaginationObject(JText::_('End'));

		if ($this->get('pages.current') < $this->get('pages.total'))
		{
			$next = $this->get('pages.current') * $this->limit;
			$end  = ($this->get('pages.total') -1) * $this->limit;

			$data->next->base	= $next;
			$data->next->link	= JRoute::_($this->uriPart . '='  .$next);
			$data->end->base	= $end;
			$data->end->link	= JRoute::_($this->uriPart . '=' . $end);
		}

		$data->pages = array();
		$stop = $this->get('pages.stop');
		for ($i = $this->get('pages.start'); $i <= $stop; $i ++)
		{
			$offset = ($i -1) * $this->limit;

			$offset = $offset == 0 ? '' : $offset;  //set the empty for removal from route

			$data->pages[$i] = new CdWebGalleryJPaginationObject($i);
			if ($i != $this->get('pages.current') || $this->_viewall)
			{
				$data->pages[$i]->base	= $offset;
				$data->pages[$i]->link	= JRoute::_($this->uriPart . '=' . $offset);
			}
		}
		return $data;
	}
}

/**
 * Pagination object representing a particular item in the pagination lists
 *
 * @package 	Joomla.Framework
 * @subpackage	HTML
 * @since		1.5
 */
class CdWebGalleryJPaginationObject extends JObject
{
	var $text;
	var $base;
	var $link;

	function __construct($text, $base=null, $link=null)
	{
		$this->text = $text;
		$this->base = $base;
		$this->link = $link;
	}
}