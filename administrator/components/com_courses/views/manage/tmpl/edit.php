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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$text = ($this->task == 'edit' ? JText::_('EDIT') : JText::_('NEW'));

$canDo = CoursesHelper::getActions('course');

JToolBarHelper::title(JText::_('COM_COURSES').': <small><small>[ ' . $text . ' ]</small></small>', 'courses.png');
if ($canDo->get('core.edit')) 
{
	JToolBarHelper::save();
}
JToolBarHelper::cancel();

jimport('joomla.html.editor');

$editor =& JEditor::getInstance();

$paramsClass = 'JParameter';
if (version_compare(JVERSION, '1.6', 'ge'))
{
	$paramsClass = 'JRegistry';
}
$gparams = new $paramsClass($this->course->params);

$membership_control = $gparams->get('membership_control', 1);

$display_system_users = $gparams->get('display_system_users', 'global');
?>
<script type="text/javascript">
function submitbutton(pressbutton) 
{
	var form = document.adminForm;
	
	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}
	
	// form field validation
	if (form.description.value == '') {
		alert('<?php echo JText::_('COM_COURSES_ERROR_MISSING_INFORMATION'); ?>');
	} else if (form.cn.value == '') {
		alert('<?php echo JText::_('COM_COURSES_ERROR_MISSING_INFORMATION'); ?>');
	} else {
		submitform(pressbutton);
	}
}
</script>
<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getError()); ?></p>
<?php } ?>
<form action="index.php" method="post" name="adminForm" id="item-form">
	<div class="col width-60 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('COM_COURSES_DETAILS'); ?></span></legend>
			
			<input type="hidden" name="course[gidNumber]" value="<?php echo $this->course->gidNumber; ?>" />
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
			<input type="hidden" name="task" value="save" />
			
			<table class="admintable">
				<tbody>
					<tr>
						<td class="key"><label for="type"><?php echo JText::_('TYPE'); ?>:</label></td>
						<td>
							<select name="course[type]">
								<option value="1"<?php echo ($this->course->type == '1') ? ' selected="selected"' : ''; ?>><?php echo JText::_('hub'); ?></option>
								<option value="0"<?php echo ($this->course->type == '0') ? ' selected="selected"' : ''; ?>><?php echo JText::_('system'); ?></option>
								<option value="2"<?php echo ($this->course->type == '2') ? ' selected="selected"' : ''; ?>><?php echo JText::_('project'); ?></option>
								<option value="3"<?php echo ($this->course->type == '3') ? ' selected="selected"' : ''; ?>><?php echo JText::_('Special Unbranded Course'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="key"><label for="firstname"><?php echo JText::_('COM_COURSES_CN'); ?>:</label></td>
						<td><input type="text" name="course[cn]" id="cn" value="<?php echo $this->escape(stripslashes($this->course->cn)); ?>" size="50" /></td>
					</tr>
					<tr>
						<td class="key"><label for="description"><?php echo JText::_('COM_COURSES_TITLE'); ?>:</label></td>
						<td><input type="text" name="course[description]" id="description" value="<?php echo $this->escape(stripslashes($this->course->description)); ?>" size="50" /></td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="course_logo"><?php echo JText::_('COM_COURSES_LOGO'); ?>:</label></td>
						<td>
							<input type="text" name="course[logo]" value="<?php echo $this->escape($this->course->logo); ?>" size="50" />
						</td>
					</tr>
		 			<tr>
						<td class="key" valign="top"><label for="public_desc"><?php echo JText::_('COM_COURSES_EDIT_PUBLIC_TEXT'); ?>:</label></td>
						<td>
							<span class="hint"><?php echo JText::_('COM_COURSES_EDIT_PUBLIC_TEXT_HINT'); ?></span>
							<?php echo $editor->display('course[public_desc]', $this->escape(stripslashes($this->course->public_desc)), '', '', '40', '10'); ?>
						</td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="private_desc"><?php echo JText::_('COM_COURSES_EDIT_PRIVATE_TEXT'); ?>:</label></td>
						<td>
							<span class="hint"><?php echo JText::_('COM_COURSES_EDIT_PRIVATE_TEXT_HINT'); ?></span>
							<?php echo $editor->display('course[private_desc]', $this->escape(stripslashes($this->course->private_desc)), '', '', '40', '10'); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('Overview Page'); ?></span></legend>
			
			<table class="admintable">
				<tbody>
					<tr>
						<td class="key"><label for="overview_type"><?php echo JText::_('COM_COURSES_OVERVIEW_TYPE'); ?>:</label></td>
						<td>
							<select name="course[overview_type]">
								<option value="0"<?php echo ($this->course->overview_type == '0') ? ' selected="selected"' : ''; ?>><?php echo JText::_('COM_COURSES_DEFAULT_CONTENT'); ?></option>
								<option value="1"<?php echo ($this->course->overview_type == '1') ? ' selected="selected"' : ''; ?>><?php echo JText::_('COM_COURSES_CUSTOM_CONTENT'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="overview_content"><?php echo JText::_('COM_COURSES_OVERVIEW_CONTENT'); ?>:</label></td>
						<td>
							<?php echo $editor->display('course[overview_content]', $this->escape(stripslashes($this->course->overview_content)), '360px', '200px', '40', '10'); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		
	</div>
	<div class="col width-40 fltrt">
		<table class="meta" summary="<?php echo JText::_('COM_COURSES_META_SUMMARY'); ?>">
			<tbody>
				<tr>
					<th><?php echo JText::_('COM_COURSES_ID'); ?></th>
					<td><?php echo $this->escape($this->course->gidNumber); ?></td>
				</tr>
<?php if ($this->course->created) { ?>
				<tr>
					<th><?php echo JText::_('Created'); ?></th>
					<td><?php echo $this->escape($this->course->created); ?></td>
				</tr>
<?php } ?>
<?php if ($this->course->created_by) { ?>
				<tr>
					<th><?php echo JText::_('Creator'); ?></th>
					<td><?php 
					$creator = JUser::getInstance($this->course->created_by);
					echo $this->escape($creator->get('name')); ?></td>
				</tr>
<?php } ?>
			</tbody>
		</table>
		
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('Membership'); ?></span></legend>
			
			<table class="admintable">
				<tbody>
					<tr>
						<td class="key" valign="top"><label for="plugin_params"><?php echo JText::_('COM_COURSES_MEMBERSHIP_CONTROL'); ?>:</label></td>
						<td>
							<input type="checkbox" name="course[params][membership_control]" id="membership_control" value="1" <?php if ($membership_control == 1) { ?>checked="checked"<?php } ?> />
							Control membership within the course?
						</td>
					</tr>
					<tr>
						<td class="key"><label for="join_policy"><?php echo JText::_('COM_COURSES_JOIN_POLICY'); ?>:</label></td>
						<td>
							<input type="radio" name="course[join_policy]" value="0"<?php if ($this->course->join_policy == 0) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_JOIN_POLICY_PUBLIC'); ?><br />
							<input type="radio" name="course[join_policy]" value="1"<?php if ($this->course->join_policy == 1) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_JOIN_POLICY_RESTRICTED'); ?><br />
							<input type="radio" name="course[join_policy]" value="2"<?php if ($this->course->join_policy == 2) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_JOIN_POLICY_INVITE'); ?><br />
							<input type="radio" name="course[join_policy]" value="3"<?php if ($this->course->join_policy == 3) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_JOIN_POLICY_CLOSED'); ?>
						</td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="restrict_msg"><?php echo JText::_('COM_COURSES_EDIT_CREDENTIALS'); ?>:</label></td>
						<td>
							<?php echo $editor->display('course[restrict_msg]', $this->escape(stripslashes($this->course->restrict_msg)), '', '', '40', '10'); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('Access'); ?></span></legend>
			
			<table class="admintable">
				<tbody>
					<tr>
						<td class="key"><label for="privacy"><?php echo JText::_('COM_COURSES_PRIVACY'); ?>:</label></td>
						<td>
							<input type="radio" name="course[privacy]" value="0"<?php if ($this->course->privacy == 0) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_ACCESS_PUBLIC'); ?><br />
							<input type="radio" name="course[privacy]" value="1"<?php if ($this->course->privacy == 1) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_ACCESS_PROTECTED'); ?><br />
							<input type="radio" name="course[privacy]" value="4"<?php if ($this->course->privacy == 4) { echo ' checked="checked"'; } ?> /> <?php echo JText::_('COM_COURSES_ACCESS_PRIVATE'); ?>
						</td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="plugin_params"><?php echo JText::_('COM_COURSES_PLUGIN_ACCESS'); ?>:</label></td>
						<td>
							<textarea name="course[plugins]" rows="10" cols="50"><?php echo $this->escape($this->course->plugins); ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2"><hr /></td>
					</tr>
					<tr>
						<td class="key" valign="top"><label for="plugin_params"><?php echo JText::_('COM_COURSES_SHOW_SYSTEM_USERS'); ?>:</label></td>
						<td>
							<select name="course[params][display_system_users]" id="display_system_users">
								<option <?php if ($display_system_users == 'global') { echo 'selected="selected"'; } ?> value="global">Global</option>
								<option <?php if ($display_system_users == 'no') { echo 'selected="selected"'; } ?> value="no">No</option>
								<option <?php if ($display_system_users == 'yes') { echo 'selected="selected"'; } ?> value="yes">Yes</option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>
	<div class="clr"></div>

<?php if (version_compare(JVERSION, '1.6', 'ge')) { ?>
	<?php if ($canDo->get('core.admin')): ?>
	<div class="col width-100 fltlft">
		<fieldset class="panelform">
			<legend><span><?php echo JText::_('COM_COURSES_FIELDSET_RULES'); ?></span></legend>
			<?php echo $this->form->getLabel('rules'); ?>
			<?php echo $this->form->getInput('rules'); ?>
		</fieldset>
	</div>
	<div class="clr"></div>
	<?php endif; ?>
<?php } ?>

	<?php echo JHTML::_('form.token'); ?>
</form>
