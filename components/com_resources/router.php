<?php
/**
 * @package     hubzero-cms
 * @author      Shawn Rice <zooley@purdue.edu>
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

function ResourcesBuildRoute(&$query)
{
    $segments = array();

    if (!empty($query['id'])) {
		$segments[] = $query['id'];
		unset($query['id']);
	}
    if (!empty($query['alias'])) {
		$segments[] = $query['alias'];
		unset($query['alias']);
	}
	if (!empty($query['active'])) {
		$segments[] = $query['active'];
		unset($query['active']);
	}
	if (!empty($query['task'])) {
		$segments[] = $query['task'];
		unset($query['task']);
	}
	if (!empty($query['file'])) {
		$segments[] = $query['file'];
		unset($query['file']);
	}
	if (!empty($query['type'])) {
		$segments[] = $query['type'];
		unset($query['type']);
	}

    return $segments;
}

function ResourcesParseRoute($segments)
{
	$vars = array();

	if (empty($segments[0]))
		return $vars;

	if (is_numeric($segments[0])) {
		$vars['id'] = $segments[0];
	} elseif ($segments[0] == 'browse') {
		$vars['task'] = $segments[0];
	} else {
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_resources'.DS.'tables'.DS.'type.php');
		
		$database =& JFactory::getDBO();
		
		$t = new ResourcesType( $database );
		$types = $t->getMajorTypes();
		
		// Normalize the title
		// This is so we can determine the type of resource to display from the URL
		// For example, /resources/learningmodules => Learning Modules
		for ($i = 0; $i < count($types); $i++) 
		{	
			$normalized = preg_replace("/[^a-zA-Z0-9]/", "", $types[$i]->type);
			$normalized = strtolower($normalized);
			
			if (trim($segments[0]) == $normalized) {
				$vars['type'] = $segments[0];
				$vars['task'] = 'browsetags';
			}
		}
		
		if ($segments[0] == 'license') {
			$vars['task'] = $segments[0];
		} else {
			if (!isset($vars['type'])) {
				$vars['alias'] = $segments[0];
			}
		}
	}

	if (!empty($segments[1])) {
		switch ($segments[1]) 
		{
			case 'download': $vars['task'] = 'download'; break;
			case 'play':     $vars['task'] = 'play';     break;
			//case 'license':  $vars['task'] = 'license';  break;
			case 'citation': $vars['task'] = 'citation'; break;
			case 'feed.rss': $vars['task'] = 'feed';     break;
			case 'feed':     $vars['task'] = 'feed';     break;
			
			default: $vars['active'] = $segments[1]; break;
		}
	}

	return $vars;
}

?>