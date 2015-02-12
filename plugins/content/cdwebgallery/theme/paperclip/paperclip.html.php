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
?>
		
<div class="webgallery">
	<?php if ($title): ?>
		<h3 class="title"><?php echo $title; ?></h3>
	<?php endif; ?>
	
	<div class="webgallery_paperclip">
		<div class="bg">
			<?php if ($indent): ?>
				<div style="margin-left: <?php echo $indent; ?>;">
			<?php endif; ?>
			<?php foreach($tags as $key=>$tag): ?>
				<div class="thumb"<?php if($params->get('only_first', 0) and $key > 0) { echo ' style="display: none;"'; } ?>>
					<?php echo str_replace('><', '><span>&nbsp;</span>' . $tag->img_thumb . '<', $tag->a); ?>
					<?php if ($thumbnail_title and $tag->title): ?>
						<span><?php echo $tag->title; ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php if ($indent): ?>
			</div>
		<?php endif; ?>
		<hr class="webgallery_clr" />
		<?php if ($pagination): ?>
			<?php echo $pagination_template; ?>
		<?php endif; ?>
		</div>
	</div>
</div>