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
defined('_JEXEC') or die( 'Restricted access' );

?>
<div id="content-header">
	<h2><?php echo $this->title; ?></h2>
</div>

<?php if ($this->juser->get('guest')) { ?>
<div id="content-header-extra">
	<ul id="useroptions">
		<li class="last"><a class="add btn" href="<?php echo JRoute::_('index.php?option=com_register'); ?>"><?php echo JText::_('Join now!'); ?></a></li>
	</ul>
</div><!-- / #content-header-extra -->
<?php } ?>

<div id="introduction" class="section">
	<div class="aside">
		<ul>
			<li>
				<a href="/user/remind">Forgot your username?</a>
			</li>
			<li>
				<a href="/user/reset">Forgot your password?</a>
			</li>
			<li>
				<a class="popup" href="<?php echo JRoute::_('index.php?option=com_help&component=members'); ?>">Need Help?</a>
			</li>
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_groups'); ?>">Groups</a>
			</li>
		</ul>
	</div><!-- / .aside -->
	<div class="subject">
		<div class="two columns first">
			<h3>Why be a member?</h3>
			<p>As a member, you instantly become part of a community designed
for you and your colleagues.  Being part of the community provides quick and easy access to share knowledge with
fellow researchers around the world helping you achieve more of your
goals.  Membership is free, get started today!</p>
		</div>
		<div class="two columns second">
			<h3>How do I become a member?</h3>
			<p>To become a member, click on the register link at the top of the page,
create a username and password, and complete the rest of the form.  After
submitting, you will receive a confirmation email momentarily; please
follow the instructions within.  You are now part of the unique experience
that is the HUB!</p>
		</div>
		<div class="clear"></div>
	</div><!-- / .subject -->
	<div class="clear"></div>
</div><!-- / #introduction.section -->

<div class="section">

	<div class="four columns first">
		<h2>Find members</h2>
	</div><!-- / .four columns first -->
	<div class="four columns second third fourth">
		<div class="two columns first">
			<form action="<?php echo JRoute::_('index.php?option=' . $this->option . '&task=browse'); ?>" method="get" class="search">
				<fieldset>
					<p>
						<label for="gsearch">Keyword or phrase:</label>
						<input type="text" name="search" id="gsearch" value="" />
						<input type="submit" value="Search" />
					</p>
					<p>
						Search public members. Members with private profiles do not show up in results.
					</p>
				</fieldset>
			</form>
		</div><!-- / .two columns first -->
		<div class="two columns second">
			<div class="browse">
				<p><a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&task=browse'); ?>">Browse the list of available members</a></p>
				<p>A list of all public members. Members with private profiles do not show up in results.</p>
			</div><!-- / .browse -->
		</div><!-- / .two columns second -->
	</div><!-- / .four columns second third fourth -->
	<div class="clear"></div>

	<div class="four columns first">
		<h2>Top contributors</h2>
	</div><!-- / .four columns first -->
	<div class="four columns second third fourth">
<?php
	$c = new MembersProfile(JFactory::getDBO());
	
	$filters = array(
		'limit'  => 4,
		'start'  => 0,
		'show'   => 'contributors',
		'sortby' => 'contributions',
		'public' => 1,
		'authorized' => false
	);
	
	if ($rows = $c->getRecords($filters, false))
	{
		$i = 0;
		foreach ($rows as $row)
		{
			if ($i == 2)
			{
				$i = 0;
			}

			switch ($i)
			{
				case 2: $cls = 'third'; break;
				case 1: $cls = 'second'; break;
				case 0: 
				default: $cls = 'first'; break;
			}

			$contributor = Hubzero_User_Profile::getInstance($row->uidNumber);
?>
		<div class="two columns <?php echo $cls; ?>">
			<div class="contributor">
				<p class="contributor-photo">
					<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&id=' . $contributor->get('uidNumber')); ?>">
						<img src="<?php echo Hubzero_User_Profile_Helper::getMemberPhoto($contributor, 0); ?>" alt="<?php echo JText::sprintf('%s\'s photo', $this->escape(stripslashes($contributor->get('name')))); ?>" />
					</a>
				</p>
				<div class="contributor-content">
					<h4 class="contributor-name">
						<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&id=' . $contributor->get('uidNumber')); ?>">
							<?php echo $this->escape(stripslashes($contributor->get('name'))); ?>
						</a>
					</h4>
				<?php if ($contributor->get('organization')) { ?>
					<p class="contributor-org">
						<?php echo $this->escape(stripslashes($contributor->get('organization'))); ?>
					</p>
				<?php } ?>
					<div class="clearfix"></div>
				</div>
				<p class="course-instructor-bio">
				<?php if ($contributor->get('bio')) { ?>
					<?php echo Hubzero_View_Helper_Html::shortenText(stripslashes($contributor->get('bio')), 200, 0); ?>
				<?php } else { ?>
					<em><?php echo JText::_('This contributor has yet to write their bio.'); ?></em>
				<?php } ?>
				</p>
			</div>
		</div><!-- / .two columns first -->
		<?php if ($i == 1) { ?>
		<div class="clear"></div>
		<?php } ?>
<?php
			$i++;
		}
	}
	else
	{
?>
		<p>No contributors found. <a href="<?php echo JRoute::_('index.php?option=com_resources&task=new'); ?>">Be the first!</a></p>
<?php
	}
?>
	</div><!-- / .four columns second third fourth -->
	<div class="clear"></div>

</div><!-- / .section -->
