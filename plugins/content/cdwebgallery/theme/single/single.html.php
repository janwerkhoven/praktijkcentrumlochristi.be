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

$img_display = $params->get('single_img_display', 'original');
?>

<div class="webgallery">
	<?php foreach($tags as $tag): ?>
		<?php if ($img_display == 'thumbnail'): ?>
			<?php $tag_img = $tag->img_thumb; ?>
		<?php else: ?>
			<?php $tag_img = $tag_img = $tag->img; ?>
		<?php endif; ?>
			<?php
			if (strpos($tag->a, 'class=') === false) {
				echo str_replace('><', ' class="webgallery_single">' . $tag_img . '<', $tag->a);
			} else {
				$modify_tag = preg_replace('#class\s?=\s?"(.*?)"#is', 'class="$1 webgallery_single"', $tag->a);
				echo str_replace('><', '>' . $tag_img . '<', $modify_tag);
			}
			?>
		<?php endforeach; ?>
</div>