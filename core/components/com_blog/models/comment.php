<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Components\Blog\Models;

use Hubzero\Database\Relational;
use Hubzero\User\Profile;
use Lang;
use Date;

/**
 * Knowledgebase model for a comment
 */
class Comment extends Relational
{
	/**
	 * The table namespace
	 *
	 * @var string
	 */
	protected $namespace = 'blog';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public $orderBy = 'created';

	/**
	 * Default order direction for select queries
	 *
	 * @var  string
	 */
	public $orderDir = 'asc';

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array
	 */
	protected $rules = array(
		'content'  => 'notempty',
		'entry_id' => 'positive|nonzero'
	);

	/**
	 * Automatically fillable fields
	 *
	 * @var  array
	 */
	public $always = array(
		'created',
		'created_by'
	);

	/**
	 * Fields to be parsed
	 *
	 * @var  array
	 */
	protected $parsed = array(
		'content'
	);

	/**
	 * Return a formatted timestamp
	 *
	 * @param   string  $as  What data to return
	 * @return  string
	 */
	public function created($as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return Date::of($this->get('created'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get('created'))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get('created');
			break;
		}
	}

	/**
	 * Return a formatted timestamp
	 * 
	 * @param   string   $as  What format to return
	 * @return  boolean
	 */
	public function modified($as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return Date::of($this->get('modified'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get('modified'))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get('modified');
			break;
		}
	}

	/**
	 * Determine if record was modified
	 * 
	 * @return  boolean  True if modified, false if not
	 */
	public function wasModified()
	{
		if ($this->get('modified') && $this->get('modified') != '0000-00-00 00:00:00')
		{
			return true;
		}
		return false;
	}

	/**
	 * Defines a belongs to one relationship between article and user
	 *
	 * @return  object  \Hubzero\Database\Relationship\BelongsToOne
	 */
	public function creator()
	{
		return Profile::getInstance($this->get('created_by'));
	}

	/**
	 * Was the entry reported?
	 *
	 * @return  boolean  True if reported, False if not
	 */
	public function isReported()
	{
		if ($this->get('state') == 3)
		{
			return true;
		}
		return false;
	}

	/**
	 * Get either a count of or list of replies
	 *
	 * @param   array  $filters  Filters to apply to query
	 * @return  object
	 */
	public function replies($filters = array())
	{
		if (!isset($filters['entry_id']))
		{
			$filters['entry_id'] = $this->get('entry_id');
		}

		$entries = self::blank()->whereEquals('parent', (int) $this->get('id'));

		if (isset($filters['state']))
		{
			$entries->whereEquals('state', (int) $filters['state']);
		}

		if (isset($filters['entry_id']))
		{
			$entries->whereEquals('entry_id', (int) $filters['entry_id']);
		}

		return $entries;
	}

	/**
	 * Get parent comment
	 *
	 * @return  object
	 */
	public function parent()
	{
		return self::oneOrFail($this->get('parent', 0));
	}

	/**
	 * Delete the record and all associated data
	 *
	 * @return  boolean  False if error, True on success
	 */
	public function destroy()
	{
		// Can't delete what doesn't exist
		if ($this->isNew())
		{
			return true;
		}

		// Remove comments
		foreach ($this->replies() as $comment)
		{
			if (!$comment->destroy())
			{
				$this->setError($comment->getError());
				return false;
			}
		}

		return parent::destroy();
	}
}

