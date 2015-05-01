<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Debug;

use Hubzero\Base\Object;
use Hubzero\Debug\Profile\Mark;

/**
 * Utility class to assist in the process of benchmarking the execution
 * of sections of code to understand where time is being spent.
 */
class Profiler extends Object
{
	/**
	 * The start time.
	 *
	 * @var  integer
	 */
	protected $started = 0;

	/**
	 * The prefix to use in the output
	 *
	 * @var  string
	 */
	protected $prefix = '';

	/**
	 * The buffer of profiling messages.
	 *
	 * @var  array
	 */
	protected $events = array();

	/**
	 * Constructor
	 *
	 * @param   string  $prefix  Prefix for mark messages
	 * @return  void
	 */
	public function __construct($prefix = '')
	{
		$this->reset();

		$this->prefix  = $prefix;
	}

	/**
	 * Reset the profiler
	 *
	 * @return  void
	 */
	public function reset()
	{
		$this->started  = $this->now();
		$this->prefix   = '';
		$this->marks    = array();
	}

	/**
	 * Get the prefix
	 *
	 * @return  string
	 */
	public function label()
	{
		return $this->prefix;
	}

	/**
	 * Output a time mark
	 *
	 * The mark is returned as text enclosed in <div> tags
	 * with a CSS class of 'profiler'.
	 *
	 * @param   string  $label  A label for the time mark
	 * @return  string  Mark enclosed in <div> tags
	 */
	public function mark($label)
	{
		$this->marks[] = new Mark($label, $this->ended(), $this->now());

		return $this;
	}

	/**
	 * Get the current time.
	 *
	 * @return  float  The current time
	 */
	public function now()
	{
		return microtime(true);
	}

	/**
	 * Gets the relative time of the start of the first period.
	 *
	 * @return  int  The time (in milliseconds)
	 */
	public function started()
	{
		return isset($this->marks[0]) ? $this->marks[0]->started() : $this->started;
	}

	/**
	 * Gets the relative time of the end of the last period.
	 *
	 * @return  int  The time (in milliseconds)
	 */
	public function ended()
	{
		$count = count($this->marks);

		return $count ? $this->marks[$count - 1]->ended() : $this->started;
	}

	/**
	 * Gets the duration of the events (including all periods).
	 *
	 * @return  int  The duration (in milliseconds)
	 */
	public function duration()
	{
		$total = 0;

		foreach ($this->marks as $mark)
		{
			$total += $mark->duration();
		}

		return $total;
	}

	/**
	 * Gets the max memory usage of all periods.
	 *
	 * @return  int  The memory usage (in bytes)
	 */
	public function memory()
	{
		$memory = 0;

		foreach ($this->marks as $mark)
		{
			if ($mark->memory() > $memory)
			{
				$memory = $mark->memory();
			}
		}

		return $memory;
	}

	/**
	 * Returns a summary of all timer activity so far
	 *
	 * @return  array
	 */
	public function marks()
	{
		return $this->marks;
	}

	/**
	 * Returns a summary of all timer activity so far
	 *
	 * @return  array
	 */
	public function summary()
	{
		$summary = array(
			'start'   => $this->started(),
			'end'     => $this->ended(),
			'total'   => $this->duration(),
			'memory'  => $this->memory()
		);

		return $summary;
	}

	/**
	 * Log profiler info
	 *
	 * @return  void
	 */
	public function log()
	{
		// <timstamp> <hubname> <ip-address> <app> <url> <query> <memory> <querycount> <timeinqueries> <totaltime>

		// This method is only called once per request so we don't need to
		// seperate logger instance creation from its use

		/*$logger = new \Hubzero\Log\Writer(
			new \Monolog\Logger(\JFactory::getConfig()->getValue('config.application_env')), 
			\JDispatcher::getInstance()
		);

		$path = \JFactory::getConfig()->getValue('config.log_path');

		if (is_dir('/var/log/hubzero-cms'))
		{
			$path = '/var/log/hubzero-cms';
		}

		$logger->useFiles($path . '/cmsprofile.log', 'info', "%datetime% %message%\n", "Y-m-d\TH:i:s.uP", 0640);

		$hubname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'unknown';
		$uri = JURI::getInstance()->getPath();
		$uri = strtr($uri, array(" "=>"%20"));
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'unknown'; 
		$memory = memory_get_usage(true);
		$db = \JFactory::getDBO();
		$querycount = $db->getCount();
		$querytime = $db->timer;
		$client = \JApplicationHelper::getClientInfo(\JFactory::getApplication()->getClientId())->name;
		$time = microtime(true) - $this->_start;

		$logger->info("$hubname $ip $client $uri [$query] $memory $querycount $querytime $time");*/
	}
}
