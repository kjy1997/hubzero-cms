<?php
/**
 * HUBzero CMS
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
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Short description for 'KbCategory'
 * 
 * Long description (if any) ...
 */
class CronJob extends JTable
{

	/**
	 * Description for 'id'
	 * 
	 * @var unknown
	 */
	var $id           = NULL;  // @var int(11) Primary key

	/**
	 * Description for 'title'
	 * 
	 * @var unknown
	 */
	var $title        = NULL;  // @var varchar(250)

	/**
	 * Description for 'state'
	 * 
	 * @var unknown
	 */
	var $state        = NULL;  // @var int(3)

	/**
	 * Description for 'access'
	 * 
	 * @var unknown
	 */
	var $plugin       = NULL;  // @var int(3)

	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $event        = NULL;  // @var varchar(200)
	
	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $last_run     = NULL;  // @var datetime(0000-00-00 00:00:00)
	
	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $next_run     = NULL;  // @var datetime(0000-00-00 00:00:00)

	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $recurrence   = NULL;  // @var varchar(200)
	
	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $created      = NULL;  // @var datetime(0000-00-00 00:00:00)
	
	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $created_by   = NULL;  // @var int(11)
	
	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $modified     = NULL;  // @var datetime(0000-00-00 00:00:00)

	/**
	 * Description for 'alias'
	 * 
	 * @var unknown
	 */
	var $modified_by  = NULL;  // @var int(11)

	/**
	 * Short description for '__construct'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown &$db Parameter description (if any) ...
	 * @return     void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__cron_jobs', 'id', $db);
	}

	/**
	 * Short description for 'check'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     boolean Return description (if any) ...
	 */
	public function check()
	{
		if (trim($this->title) == '') 
		{
			$this->setError(JText::_('COM_CRON_ERROR_EMPTY_TITLE'));
			return false;
		}
		
		if (!$this->recurrence) 
		{
			$this->setError(JText::_('COM_CRON_ERROR_EMPTY_RECURRENCE'));
			return false;
		}
		
		$this->recurrence = preg_replace('/[\s]{2,}/', ' ', $this->recurrence);

		if (preg_match('/[^-,* \\d]/', $this->recurrence) !== 0)
		{
			$this->setError(JText::_('Cron String contains invalid character.'));
			return false;
		}

 		$bits = @explode(' ', $this->recurrence);
		if (count($bits) != 5)
		{
			$this->setError(JText::_('Cron string is invalid. Too many or too little sections.'));
			return false;
		}
		
		return true;
	}

	/**
	 * Short description for 'buildQuery'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     string Return description (if any) ...
	 */
	public function buildQuery($filters=array())
	{
		$query  = "FROM $this->_tbl AS c";
		
		$where = array();
		
		if (isset($filters['state'])) 
		{
			$where[] = "c.state=" . $this->_db->Quote($filters['state']);
		}
		
		if (isset($filters['search']) && $filters['search'] != '') 
		{
			$where[] = "LOWER(c.title) LIKE '%" . strtolower($filters['search']) . "%'";
		}
		
		if (count($where) > 0)
		{
			$query .= " WHERE ";
			$query .= implode(" AND ", $where);
		}

		return $query;
	}

	/**
	 * Short description for 'getCount'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     object Return description (if any) ...
	 */
	public function getCount($filters=array())
	{
		$filters['limit'] = 0;

		$query = "SELECT COUNT(*) " . $this->buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Short description for 'getRecords'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     object Return description (if any) ...
	 */
	public function getRecords($filters=array())
	{
		$query  = "SELECT c.*";
		$query .= " " . $this->buildQuery($filters);

		if (!isset($filters['sort']) || !$filters['sort']) 
		{
			$filters['sort'] = 'title';
		}
		if (!isset($filters['sort_Dir']) || !$filters['sort_Dir']) 
		{
			$filters['sort_Dir'] = 'ASC';
		}
		$query .= " ORDER BY " . $filters['sort'] . " " . $filters['sort_Dir'];
		
		if (isset($filters['limit']) && $filters['limit'] != 0) 
		{
			$query .= ' LIMIT ' . $filters['start'] . ',' . $filters['limit'];
		}

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}
}

