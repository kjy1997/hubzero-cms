<?php
/**
 * @package     hubzero-cms
 * @author      Alissa Nedossekina <alisa@purdue.edu>
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

Class GroupModules extends JTable
{
	var $id = NULL;
	var $gid = NULL;
	var $type = NULL;
	var $content = NULL;
	var $morder = NULL;
	var $active = NULL;
	
	function __construct( &$db)
	{
		parent::__construct( '#__xgroups_modules', 'id', $db );
	}
	
	//-------
	
	public function getModules( $gid, $active = false ) 
	{
		if($active) {
			$sql = "SELECT * FROM $this->_tbl WHERE gid='".$gid."' AND active=1 ORDER BY morder ASC";	
		} else {
			$sql = "SELECT * FROM $this->_tbl WHERE gid='".$gid."' ORDER BY morder ASC";	
		}
		
		$this->_db->setQuery($sql);
		$modules = $this->_db->loadAssocList();
		
		return $modules;
	}
	
	//-----
	
	public function getHighestModuleOrder( $gid )
	{
		$sql = "SELECT morder from $this->_tbl WHERE gid='".$gid."' ORDER BY morder DESC LIMIT 1";
		$this->_db->setQuery($sql);
		$high = $this->_db->loadAssoc();
		
		return $high['morder'];
	}
	
	//-----
	
	function renderModules ( $group, $wiki_parser )
	{
		//array of modules
		$raw_modules = array();
		
		//all modules even deactivated modules
		$raw_modules_all = $this->getModules($group->get('gidNumber'), false);
		
		//get the modules for this group
		$raw_modules = $this->getModules($group->get('gidNumber'), true);
		
		//if there are no modules sent use default
		if(count($raw_modules_all) < 1) {
			$default_module = array();
			$default_module['type'] = 'information';
			$default_module['content'] = '';
			array_push($raw_modules, $default_module);
		}
		
		//base path to modules folder
		$path = JPATH_COMPONENT . DS . 'modules' . DS;

		//module content
		$return = '';
		
		
		//foreach of the group modules in the db render them
		foreach($raw_modules as $mod) {
			//check to make sure group module has php file to render it
			if(is_file($path . $mod['type'].'.php')) {
				//include the php file
				include_once($path . $mod['type'].'.php');
				
				//class name is module type + Macro (Ex. CustomModule)
				$class_name = ucfirst($mod['type'].'Module');
				
				//if class name exists then instantiate and push the module content to the file to render out final module
				if(class_exists($class_name)) {
					$module = new $class_name( $group );
					$module->content = $mod['content'];
					$output = $module->render();
				} else {
					$output = '';
				}
				
				//append out of render to final output
				$return .= $output;
			} else {
				$return .= "<div class=\"group_module_custom\"><small>".JTEXT::sprintf('GROUP_MODULE_NOT_INSTALLED', ucfirst($mod['type']))."</small></div>";
			}
		}
		
		//return rendered modules
		return $return;
	}
	
	
}
?>