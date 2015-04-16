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

namespace Hubzero\Template;

use Hubzero\Container\Container;
use Exception;
use stdClass;

/**
 * Component helper class
 */
class Loader
{
	/**
	 * The application implementation.
	 *
	 * @var  object
	 */
	protected $app;

	/**
	 * The component list cache
	 *
	 * @var  array
	 */
	protected static $components = array();

	/**
	 * Constructor
	 *
	 * @param   object  $app
	 * @return  void
	 */
	public function __construct(Container $app)
	{
		self::$components = array();

		$this->app = $app;
	}

	/**
	 * Checks if the template is enabled
	 *
	 * @param   string   $option  The component option.
	 * @param   boolean  $strict  If set and the component does not exist, false will be returned.
	 * @return  boolean
	 */
	public function isEnabled($name, $client_id = 0)
	{
		$result = $this->load($name, $client_id);

		return ($result->name == $name);
	}

	/**
	 * Gets the parameter object for the component
	 *
	 * @param   string   $option     The option for the component.
	 * @param   integer  $client_id  If set and the component does not exist, false will be returned
	 * @return  object   A JRegistry object.
	 */
	public function params($name, $client_id = 0)
	{
		return $this->load($name, $client_id)->params;
	}

	/**
	 * Make sure template name follows naming conventions
	 *
	 * @param   string  $name
	 * @return  string
	 */
	public function canonical($name)
	{
		return preg_replace('/[^A-Z0-9_\.-]/i', '', $name);
	}

	/**
	 * Make sure component name follows naming conventions
	 *
	 * @param   string   $name       The template name
	 * @param   integer  $client_id  The client to load the tmeplate for
	 * @return  string
	 */
	public function load($name = null, $client_id = 0)
	{
		$method = 'get' . ucfirst($this->app['client']->name) . 'Template';

		if (method_exists($this, $method))
		{
			return $this->$method();
		}

		return $this->getSystemTemplate();

		/*$client = $this->app['client']->url;

		// Load the template name from the database
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, home, template AS name, s.params');
		$query->from('#__template_styles as s');
		$query->leftJoin('#__extensions as e ON e.type=' . $db->quote('template') . ' AND e.element=s.template AND e.client_id=s.client_id');
		$query->where('s.client_id = ' . $this->app['client']->id);
		$query->where('e.enabled = 1');
		if ($style = $this->app['user']->getParam($client . '_style'))
		{
			$query->where('id = ' . (int) $style . ' AND e.enabled = 1', 'OR');
		}
		$query->order('home');
		$db->setQuery($query);
		$template = $db->loadObject();*/
	}

	/**
	 * Get the system template
	 *
	 * @return  object
	 */
	public function getSystemTemplate()
	{
		$template = new stdClass;
		$template->id       = 0;
		$template->home     = 0;
		$template->template = 'system';
		$template->params   = new \JRegistry();

		return $template;
	}

	/**
	 * Get the admin template
	 *
	 * @return  object
	 */
	public function getAdministratorTemplate()
	{
		// Load the template name from the database
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('s.id, s.home, s.template, s.params');
		$query->from('#__template_styles as s');
		$query->leftJoin('#__extensions as e ON e.type='.$db->quote('template').' AND e.element=s.template AND e.client_id=s.client_id');
		if ($style = \User::getParam('admin_style'))
		{
			$query->where('s.client_id = 1 AND id = ' . (int) $style . ' AND e.enabled = 1', 'OR');
		}
		$query->where('s.client_id = 1 AND home = 1', 'OR');
		$query->order('home');
		$db->setQuery($query);

		$template = $db->loadObject();
		$template->template = $this->canonical($template->template);
		$template->params   = new \JRegistry($template->params);

		if (!file_exists(JPATH_THEMES . DS . $template->template . DS . 'index.php'))
		{
			$template = $this->getSystemTemplate();
		}

		return $template;
	}

	/**
	 * Get the site template
	 *
	 * @return  object
	 */
	public function getSiteTemplate()
	{
		// Get the id of the active menu item
		$menu = \JFactory::getApplication()->getMenu();
		$item = $menu->getActive();
		if (!$item)
		{
			$item = $menu->getItem($this->app['request']->getInt('Itemid'));
		}

		$id = 0;
		if (is_object($item))
		{
			// valid item retrieved
			$id = $item->template_style_id;
		}
		$condition = '';

		$tid = $this->app['request']->getVar('templateStyle', 0);
		if (is_numeric($tid) && (int) $tid > 0)
		{
			$id = (int) $tid;
		}

		$cache = \JFactory::getCache('com_templates', '');
		$tag = '';
		/*if ($this->_language_filter)
		{
			$tag = $this->app['language']->getTag();
		}*/

		if (!$templates = $cache->get('templates0' . $tag))
		{
			// Load styles
			$db = \JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('s.id, s.home, s.template, s.params');
			$query->from('#__template_styles as s');
			$query->where('s.client_id = 0');
			$query->where('e.enabled = 1');
			$query->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

			$db->setQuery($query);
			$templates = $db->loadObjectList('id');
			foreach ($templates as &$template)
			{
				$registry = new \JRegistry;
				$registry->loadString($template->params);
				$template->params = $registry;

				// Create home element
				if ($template->home == 1 && !isset($templates[0])) // || $this->_language_filter && $template->home == $tag)
				{
					$templates[0] = clone $template;
				}
			}
			$cache->store($templates, 'templates0' . $tag);
		}

		if (isset($templates[$id]))
		{
			$template = $templates[$id];
		}
		else
		{
			// [!] zooley - Fixing template fallback to always load system template if current one is not found.
			//     Previous way could cause code to get stuck in a loop and run out of memory.
			if (isset($templates[0]))
			{
				$template = $templates[0];
			}
			else
			{
				$template = new stdClass;
				$template->params = new \JRegistry;
				$template->home   = 0;
			}
			$template->template = 'system';
			$template->id   = 0;
		}

		// Allows for overriding the active template from the request
		$template->template = $this->app['request']->getCmd('template', $template->template);
		$template->template = $this->canonical($template->template); // need to filter the default value as well

		// Fallback template
		if (!file_exists(JPATH_THEMES . DS . $template->template . DS . 'index.php'))
		{
			$template = $this->getSystemTemplate();
		}

		return $template;
	}
}
