<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 * All rights reserved.
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

// Set the path
$path = rtrim($this->model->path($this->course->get('id'), false), DS);

// Get the manifest
if (is_dir(JPATH_ROOT . $path))
{
	$manifests = JFolder::files(JPATH_ROOT . $path, '.json', true, true);
	$manifest  = (count($manifests) > 0) ? $manifests[0] : '';
}

if (isset($manifest) && is_file($manifest))
{
	$media_path = $manifest;
	$media_dir  = dirname($manifest);
	$manifest   = json_decode(file_get_contents($manifest));

	if (is_null($manifest))
	{
		$type  = 'none';
		$error = JText::_('COM_COURSES_VIDEO_ERROR_INVALID_JSON');
	}
	else
	{
		$type = (isset($manifest->presentation->slides)) ? 'hubpresenter' : 'html5';
	}
}
else if (in_array(substr($this->model->get('url'), -3), array('mov', 'mp4', 'm4v', 'ogg', 'ogv', 'webm')))
{
	$type = 'standalone';
}
else
{
	$type = 'none';
}

JHTML::_('behavior.framework', true);

// If the video type is 'hubpresenter', perform next steps
if ($type == 'hubpresenter')
{
	// Check if path exists
	if (is_dir($media_dir))
	{
		// Get all files matching  /.mp4|.webs|.ogv|.m4v|.mp3/
		$media = JFolder::files($media_dir, '.mp4|.webm|.ogv|.m4v|.mp3', false, false);
		$ext = array();
		foreach ($media as $m)
		{
			$pieces = explode('.', $m);
			$ext[]  = array_pop($pieces);
		}

		// If we dont have all the necessary media formats
		if ((in_array('mp4', $ext) && count($ext) < 3) || (in_array('mp3', $ext) && count($ext) < 2))
		{
			$this->setError(JText::_('COM_COURSES_VIDEO_ERROR_MISSING_FORMATS'));
		}

		// Make sure if any slides are video we have three formats of video and backup image for mobile
		$slide_path = $media_dir . DS . 'slides';
		$slides = JFolder::files($slide_path, '', false, false);

		// Array to hold slides with video clips
		$slide_video = array();

		// Build array for checking slide video formats
		foreach ($slides as $s)
		{
			$parts = explode('.', $s);
			$ext = array_pop($parts);
			$name = implode('.', $parts);

			if (in_array($ext, array('mp4', 'm4v', 'webm', 'ogv')))
			{
				$slide_video[$name][$ext] = $name . '.' . $ext;
			}
		}

		// Make sure for each of the slide videos we have all three formats and has a backup image for the slide
		foreach ($slide_video as $k => $v)
		{
			if (count($v) < 3)
			{
				$this->setError(JText::sprintf('COM_COURSES_VIDEO_ERROR_MISSING_SLIDES_FORMAT', count($v), $k . implode(", {$k}.", array_keys($v))));
			}

			if (!file_exists($slide_path . DS . $k . '.png'))
			{
				$this->setError(JText::sprintf('COM_COURSES_VIDEO_ERROR_MISSING_STILL_IMAGE', $k));
			}
		}
	}

	// Get the manifest for the presentation
	$contents = file_get_contents($media_path);

	// Content folder
	$content_folder = ltrim(rtrim($media_dir, DS), JPATH_ROOT);

	// Decode the json formatted manifest so we can use the information
	$presentation = json_decode($contents);
	$presentation = $presentation->presentation;

	// get subs from json file
	$subs = (isset($presentation->subtitles)) ? $presentation->subtitles : array();

	// make sure source is full path to assets folder
	$subFiles = array();
	foreach ($subs as $k => $subtitle)
	{
		if (!strpos($subtitle->source, DS))
		{
			$subtitle->source = $content_folder . DS . $subtitle->source;
		}

		$subFiles[] = $subtitle->source;
	}

	// get local subs
	$local_subs = array();
	if (is_dir($content_folder))
	{
		$local_subs = JFolder::files(JPATH_ROOT . DS . $content_folder, '.srt|.SRT', true, false);
	}

	// add local subtitles too
	foreach ($local_subs as $k => $subtitle)
	{
		$info     = pathinfo($subtitle);
		$name     = str_replace('-auto','', $info['filename']);
		$autoplay = (strstr($info['filename'],'-auto')) ? 1 : 0;
		$source   = $content_folder . DS . $subtitle;

		// add each subtitle
		$subtitle                  = new stdClass;
		$subtitle->type            = 'SRT';
		$subtitle->name            = ucfirst($name);
		$subtitle->source          = $source;
		$subtitle->autoplay        = $autoplay;

		// make sure we dont already have this file.
		if (!in_array($subtitle->source, $subFiles))
		{
			$subs[] = $subtitle;
		}
	}

	// Add the HUBpresenter stylesheet and scripts
	\Hubzero\Document\Assets::addComponentStylesheet('com_resources', "/assets/css/hubpresenter.css");
	\Hubzero\Document\Assets::addComponentStylesheet('com_courses', "/assets/css/hubpresenter.css");

	\Hubzero\Document\Assets::addComponentScript('com_resources', "assets/js/hubpresenter");
	\Hubzero\Document\Assets::addComponentScript('com_resources', "assets/js/hubpresenter.plugins");

	\Hubzero\Document\Assets::addSystemScript('jquery.colpick');
	\Hubzero\Document\Assets::addSystemStylesheet('jquery.colpick');

}
elseif ($type == 'html5')
{
	\Hubzero\Document\Assets::addComponentStylesheet('com_resources', "/assets/css/video.css");
	\Hubzero\Document\Assets::addComponentStylesheet('com_courses', "/assets/css/video.css");

	\Hubzero\Document\Assets::addComponentScript('com_resources', "assets/js/video");
	\Hubzero\Document\Assets::addComponentScript('com_resources', "assets/js/hubpresenter.plugins");

	$presentation = $manifest->presentation;

	// Determine height and width
	$width  = (isset($presentation->width) && $presentation->width != 0) ? $presentation->width . 'px' : 'auto';
	$height = (isset($presentation->height) && $presentation->height != 0) ? $presentation->height . 'px' : 'auto';
}

if ($type == 'hubpresenter' || $type == 'html5')
{
	// Include media tracking for html5 and hubpresenter videos
	require_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'media.tracking.php');
	$dbo = JFactory::getDBO();
	$mediaTracking = new ResourceMediaTracking($dbo);

	// Get tracking for this user for this resource
	$tracking = $mediaTracking->getTrackingInformationForUserAndResource(JFactory::getUser()->get('id'), $this->asset->id, 'course');

	// Check to see if we already have a time query param
	$hasTime = (JRequest::getVar('time', '') != '') ? true : false;

	// Do we want to redirect user with time added to url
	if (is_object($tracking) && !$hasTime && $tracking->current_position > 0 && $tracking->current_position != $tracking->object_duration)
	{
		$redirect = JURI::current();

		// do we have tmpl=componet in url?
		$delimeter = (strpos($redirect, '?') === false) ? '?' : '&';
		if (JRequest::getVar('tmpl', '') == 'component')
		{
			$redirect .= $delimeter . 'tmpl=component';
		}

		$delimeter = (strpos($redirect, '?') === false) ? '?' : '&';

		// Append current position to redirect
		$redirect .= $delimeter . 'time=' . gmdate("H:i:s", $tracking->current_position);

		// Redirect
		JFactory::getApplication()->redirect(JRoute::_($redirect, false), '','',false);
	}
}

?>
<?php if ($type == 'html5') : ?>
	<div id="video-container">
		<?php if (count($presentation->media) > 0) : ?>
			<video controls="controls" id="video-player" data-mediaid="<?php echo $this->asset->id; ?>">
				<?php foreach ($presentation->media as $video) : ?>
					<?php
						switch ($video->type)
						{
							case 'ogg':
							case 'ogv':  $type = 'video/ogg;';  break;
							case 'webm': $type = 'video/webm;'; break;
							case 'mp4':
							case 'm4v':
							default:     $type = 'video/mp4;';  break;
						}

						//video source
						$source = $video->source;

						//is this the mp4 (need for flash)
						if (in_array($video->type, array('mp4','m4v')))
						{
							$mp4 = $video->source;
						}

						//if were playing local files
						if (substr($video->source, 0, 4) != 'http')
						{
							$source = $base . $source;
							if (in_array($video->type, array('mp4','m4v')))
							{
								$mp4 = $base . $mp4;
							}
						}
					?>
					<source src="<?php echo $source; ?>" type="<?php echo $type; ?>" />
				<?php endforeach; ?>

				<a href="<?php echo $mp4; ?>"
					id="video-flowplayer"
					style="<?php echo "width:{$width};height:{$height};"; ?>"
					data-mediaid="<?php echo $this->asset->id; ?>"></a>

				<?php if (count($presentation->subtitles) > 0) : ?>
					<?php foreach ($presentation->subtitles as $subtitle) : ?>
						<?php
							//get file modified time
							$source = $subtitle->source;
							$auto   = $subtitle->autoplay;

							//if were playing local files
							if (substr($subtitle->source, 0, 4) != 'http')
							{
								$source   = $base . $source;
								$modified = filemtime( JPATH_ROOT . $source );
							}
							else
							{
								$modified = '123456789';
							}
						?>
						<div
							data-autoplay="<?php echo $auto; ?>"
							data-type="subtitle"
							data-lang="<?php echo $subtitle->name; ?>"
							data-src="<?php echo $source ?>?v=<?php echo $modified; ?>"></div>
					<?php endforeach; ?>
				<?php endif; ?>
			</video>
		<?php endif; ?>
	</div><!-- /#video-container -->
	<div id="transcript-container">
		<div id="transcript-toolbar">
			<select id="transcript-selector"></select>
			<input type="text" id="transcript-search" placeholder="<?php echo JText::_('COM_COURSES_VIDEO_SEARCH_TRANSCRIPT'); ?>" />
			<a href="javascript:void(0);" id="font-bigger"></a>
			<a href="javascript:void(0);" id="font-smaller"></a>
		</div>
		<div id="transcripts"></div>
	</div>
<?php elseif ($type == 'hubpresenter') : ?>
	<div id="presenter-container">
		<div id="presenter-header">
			<div id="title"><?php echo $this->asset->get('title'); ?></div>
		</div><!-- /#header -->

		<div id="presenter-content">
			<div id="presenter-left">
				<div id="slides">
					<ul class="no-js">
						<?php $counter = 0; ?>
						<?php foreach ($presentation->slides as $slide) : ?>
							<li id="slide_<?php echo $counter; ?>" title="<?php echo $slide->title; ?>" time="<?php echo $slide->time; ?>">
								<?php if ($slide->type == 'Image') : ?>
									<img src="<?php echo $content_folder . DS . $slide->media; ?>" alt="<?php echo $slide->title; ?>" />
								<?php else : ?>
									<video class="slidevideo">
										<?php foreach ($slide->media as $source): ?>
											<source src="<?php echo $content_folder . DS . $source->source; ?>" />
										<?php endforeach; ?>
										<a href="<?php echo $content_folder . DS . $slide->media[0]->source; ?>" class="flowplayer_slide" id="flowplayer_slide_<?php echo $counter; ?>"></a>
									</video>
									<img src="<?php echo $content_folder . DS . $slide->media[3]->source; ?>" alt="<?php echo $slide->title; ?>" class="imagereplacement" />
								<?php endif; ?>
							</li>
							<?php $counter++; ?>
						<?php endforeach; ?>
					</ul>
				</div><!-- /#slides -->

				<div id="control-box" class="no-controls" data-theme="dark">
					<div id="progress-bar"></div>
					<div id="control-buttons">
						<div id="control-buttons-left" class="cf">
							<a id="previous" class="control" href="javascript:void(0);" title="Previous Slide"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_PREV'); ?></a>
							<a id="play-pause" class="control" href="javascript:void(0);" title="Play Presentation"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_PAUSE'); ?></a>
							<a id="next" class="control" href="javascript:void(0);" title="Next Slide"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_NEXT'); ?></a>
							<div id="media-progress"></div>
						</div>
						<div id="control-buttons-right" class="cf">
							<a id="subtitle" class="control" href="javascript:void(0);">
								<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTIONS_TRANSCRIPT'); ?>
								<div class="control-container subtitle-controls">
									<h3><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTIONS_TRANSCRIPT'); ?></h3>
									<div class="grid">
										<div class="col span4 label">
											<label for="subtitle-selector"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTIONS'); ?>:</label>
										</div>
										<div class="col span8 omega input">
											<select id="subtitle-selector">
												<option value=""><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTIONS_TRANSCRIPT_OFF'); ?></option>
											</select>
										</div>
									</div>
									<div class="grid">
										<div class="col span4 label">
											<label for="transcript-selector"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_TRANSCRIPT'); ?>:</label>
										</div>
										<div class="col span8 omega input">
											<select class="transcript-selector">
												<option value=""><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTIONS_TRANSCRIPT_OFF'); ?></option>
											</select>
										</div>
									</div>

									<span class="options-toggle"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTIONS'); ?></span>
									<div class="subtitle-settings hide">
										<div class="grid">
											<div class="col span6 label">
												<label for="font-selector"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT'); ?>:</label>
											</div>
											<div class="col span6 omega input">
												<select id="font-selector">
													<option value="Arial" selected><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_ARIAL'); ?></option>
													<option value="Times New Roman"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_TIMES'); ?></option>
													<option value="Tahoma"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_TAHOMA'); ?></option>
													<option value="Trebuchet MS"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_TREBUCHET'); ?></option>
													<option value="Verdana"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_VERDANA'); ?></option>
													<option value="Courier New"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_COURIER'); ?></option>
												</select>
											</div>
										</div>
										<div class="grid">
											<div class="col span6 label">
												<label for="font-size-selector"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_SIZE'); ?>:</label>
											</div>
											<div class="col span6 omega input">
												<select id="font-size-selector">
													<option value="12"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_SIZE_SMALL'); ?></option>
													<option value="18" selected><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_SIZE_MEDIUM'); ?></option>
													<option value="24"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_SIZE_LARGE'); ?></option>
												</select>
											</div>
										</div>
										<div class="grid">
											<div class="col span6 label">
												<label for="font-color"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_FONT_COLOR'); ?>:</label>
											</div>
											<div class="col span6 omega input">
												<div id="font-color" data-color="FFF" style="background-color: #FFF;"></div>
											</div>
										</div>
										<div class="grid">
											<div class="col span6 label">
												<label for="background-color"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_BACKGROUND'); ?>:</label>
											</div>
											<div class="col span6 omega input">
												<div id="background-color" data-color="000" style="background-color: #000;"></div>
											</div>
										</div>
										<div class="grid">
											<div class="col span12 omega subtitle-settings-preview-container">
												<div class="subtitle-settings-preview">
													<div class="test" style="font-family:arial; background-color: #000; color: #FFF; font-size:18px;"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_EXAMPLE'); ?></div>
												</div>
											</div>
										</div>
										<div class="actions">
											<button class="btn btn-info btn-secondary icon-save" id="subtitle-settings-save"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_CAPTION_OPTION_SAVE'); ?></button>
										</div>
									</div>
								</div>
							</a>
							<a id="volume" class="control " href="javascript:void(0);">
								<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_VOLUME'); ?>
								<div class="control-container volume-controls">
									<div id="volume-bar"></div>
								</div>
							</a>
							<a id="settings" class="control" href="javascript:void(0);" title="Adjust Settings for Playback">
								<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS'); ?>
								<div class="control-container settings-controls">
									<h3><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS'); ?></h3>
									<div class="grid">
										<div class="col span6 label">
											<label for="speed"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE'); ?>:</label>
										</div>
										<div class="col span6 omega input">
											<select id="speed">
												<option value=".25"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_025'); ?></option>
												<option value=".5"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_05'); ?></option>
												<option selected value="1"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_NORMAL'); ?></option>
												<option value="1.25"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_125'); ?></option>
												<option value="1.5"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_15'); ?></option>
												<option value="2"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SETTINGS_PLAYBACK_RATE_2'); ?></option>
											</select>
										</div>
									</div>
									<!-- <div class="grid">
										<div class="col span6 label">
											<label for="theme">Player Theme:</label>
										</div>
										<div class="col span6 omega input">
											<select id="theme">
												<option value="dark">Dark (default)</option>
											</select>
										</div>
									</div> -->
								</div>
							</a>
							<a id="link" class="control" href="javascript:void(0);" title="<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_LINK_THIS_SPOT'); ?>">
								<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_LINK'); ?>
								<div class="control-container link-controls">
									<h3><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_LINK_TO_VIDEO'); ?> <span><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_LINK_TO_VIDEO_AT_POSITION'); ?></span></h3>
									<div class="grid">
										<div class="col span12 omega">
											<input type="text" value="ss" />
											<span class="hint"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_LINK_HINT'); ?></span>
										</div>
									</div>
								</div>
							</a>
							<a id="switch" class="control" href="javascript:void(0);" title="<?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SWITCH_VIDEO_SLIDES'); ?>"><?php echo JText::_('COM_COURSES_VIDEO_CONTROL_SWITCH'); ?></a>
						</div>
					</div>
				</div><!-- /#control-box -->
			</div><!-- /#left -->
			<?php $cls = (isset($presentation->videoPosition)
							&& $presentation->videoPosition == "left"
							&& strtolower($presentation->type) == 'video') ? "move-left": ""; ?>
			<div id="presenter-right">
				<div id="media" class="<?php echo $cls; ?>">
					<?php if (strtolower($presentation->type) == 'video') : ?>
						<video id="player" preload="auto" controls="controls" data-mediaid="<?php echo $this->asset->get('id'); ?>">
							<?php foreach ($presentation->media as $source): ?>
								<?php
									switch (strtolower($source->type))
									{
										case 'm4v':
										case 'mp4':  $type = 'video/mp4;';  break;
										case 'ogv':  $type = 'video/ogg;';  break;
										case 'webm': $type = 'video/webm;'; break;
									}
								?>
								<source src="<?php echo $content_folder . DS . $source->source; ?>" type='<?php echo $type; ?>'>
							<?php endforeach; ?>
							<a href="<?php echo $content_folder . DS . $presentation->media[0]->source; ?>" id="flowplayer"></a>

							<?php if (isset($subs) && count($subs) > 0) : ?>
								<?php foreach ($subs as $sub) : ?>
									<div
										data-autoplay="<?php echo $sub->autoplay; ?>"
										data-type="subtitle"
										data-lang="<?php echo $sub->name; ?>"
										data-src="<?php echo $sub->source; ?>?v=<?php echo filemtime( JPATH_ROOT . DS . $sub->source ); ?>"></div>
								<?php endforeach; ?>
							<?php endif; ?>

						</video>
					<?php else : ?>
						<audio id="player" preload="auto" controls="controls">
							<?php foreach ($presentation->media as $source): ?>
								<source src="<?php echo $content_folder . DS . $source->source; ?>" />
							<?php endforeach; ?>
							<a href="<?php echo $content_folder . DS . $presentation->media[0]->source; ?>" id="flowplayer" duration="<?php if (isset($presentation->duration) && $presentation->duration) { echo $presentation->duration; } ?>"></a>
						</audio>
					<?php endif; ?>
					<div id="video-subtitles"></div>
				</div>
				<div id="list">
					<ul id="list_items">
						<?php $num = 0; $counter = 0; $last_slide_id = 0; ?>
						<?php foreach ($presentation->slides as $slide) : ?>
							<?php if ((int)$slide->slide != $last_slide_id) : ?>
								<li id="list_<?php echo $counter; ?>">
									<?php
										// Use thumb if possible
										$thumb = '';
										if (isset($slide->thumb) && $slide->thumb && file_exists(JPATH_ROOT.DS.$content_folder.DS.$slide->thumb))
										{
											$thumb = $content_folder.DS.$slide->thumb;
										}
										else if (!is_array($slide->media) && file_exists(JPATH_ROOT.DS.$content_folder.DS.$slide->media))
										{
											$thumb = $content_folder.DS.$slide->media;
										}
									?>
									<img src="<?php echo $thumb; ?>" alt="<?php echo $slide->title; ?>" />
									<span>
										<?php
											$num++;
											$max = 30;
											$elipsis = '&hellip;';
											echo ($num) . '. ';
											echo substr($slide->title, 0, $max);

											if (strlen($slide->title) > $max)
												echo $elipsis;
										?>
									</span>
									<span class="time"><?php echo $slide->time; ?></span>
									<div id="list-slider-<?php echo $counter; ?>" class="list-slider"></div>
									<div class="list-progress">00:00/00:00</div>
								</li>
							<?php endif; ?>
							<?php
								$last_slide_id = $slide->slide;
								$counter++;
							?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div><!-- /#right -->
		</div><!-- /#content -->
		<div id="transcript-container">
			<div id="transcript-toolbar">
				<div id="transcript-select"></div>
				<input type="text" id="transcript-search" placeholder="Search Transcript..." />
				<a href="javascript:void(0);" id="font-bigger"></a>
				<a href="javascript:void(0);" id="font-smaller"></a>
			</div>
			<div id="transcripts"></div>
		</div>
	</div>
<?php elseif ($type == 'standalone') : ?>
	<?php
		jimport('joomla.filesystem.file');
		$path = $path . DS . $this->model->get('url');
		$ext  = strtolower(JFile::getExt(JPATH_ROOT . $path));
		$doc  = JFactory::getDocument();
		$doc->addStyleSheet('//releases.flowplayer.org/5.4.2/skin/minimalist.css');
		$doc->addScript('//releases.flowplayer.org/5.4.2/flowplayer.min.js');
	?>
	<div class="flowplayer">
		<video id="movie<?php echo rand(0, 1000); ?>" preload controls>
			<?php
				switch ($ext)
				{
					case 'mov':
					case 'mp4':
					case 'm4v':
						echo '<source src="' . $path . '" type="video/mp4" />';
					break;

					case 'ogg':
					case 'ogv':
						echo '<source src="' . $path . '" type="video/ogg" />';
					break;

					case 'webm':
						echo '<source src="' . $path . '" type="video/webm" />';
					break;
				}
			?>
		</video>
	</div>
<?php else : ?>
	<p class="warning"><?php echo (isset($error)) ? $error : JText::_('COM_COURSES_VIDEO_ERROR_NO_PLAYABLE_ASSETS'); ?></p>
<?php endif; ?>
