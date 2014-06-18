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

$pathway = JFactory::getApplication()->getPathway();
$pathway->addItem(
	JText::_('File List'),
	'index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList'
);

$jconfig = JFactory::getConfig();
$juser = JFactory::getUser();

$sort = strtolower(JRequest::getVar('sort', 'created'));
if (!in_array($sort, array('created', 'filename', 'description', 'created_by')))
{
	$sort = 'created';
}
$dir = strtoupper(JRequest::getVar('dir', 'DESC'));
if (!in_array($dir, array('ASC', 'DESC')))
{
	$dir = 'DESC';
}

$limit = JRequest::getInt('limit', $jconfig->getValue('config.list_limit'));
$start = JRequest::getInt('limitstart', 0);

$database = JFactory::getDBO();

$where = " AND (wp.group_cn='' OR wp.group_cn IS NULL) ";
if ($this->sub)
{
	$parts = explode('/', $this->page->scope);
	$where = " AND wp.group_cn='" . trim($parts[0]) . "' ";
}

$query = "SELECT COUNT(*)
		FROM #__wiki_attachments AS wa
		INNER JOIN #__wiki_page AS wp
			ON wp.id=wa.pageid
		WHERE wp.scope LIKE '{$this->page->scope}%' $where";

$database->setQuery($query);
$total = $database->loadResult();

$query = "SELECT wa.*, wp.scope, wp.pagename
		FROM #__wiki_attachments AS wa
		INNER JOIN #__wiki_page AS wp
			ON wp.id=wa.pageid
		WHERE wp.scope LIKE '{$this->page->scope}%'
			$where
		ORDER BY $sort $dir";
if ($limit && $limit != 'all')
{
	$query .= " LIMIT $start, $limit";
}

$database->setQuery($query);
$rows = $database->loadObjectList();

jimport('joomla.html.pagination');
$pageNav = new JPagination(
	$total,
	$start,
	$limit
);

$altdir = ($dir == 'ASC') ? 'DESC' : 'ASC';
?>
<form method="get" action="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList'); ?>">
	<p>
		This special page shows all uploaded files of this wiki. By default the last uploaded files are shown at top of the list. A click on a column header changes the sorting. Deleted files are not shown here.
	</p>
	<div class="container">
		<table class="file entries">
			<thead>
				<tr>
					<th scope="col">
						<a<?php if ($sort == 'created') { echo ' class="active"'; } ?> href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList&sort=created&dir=' . $altdir); ?>">
							<?php if ($sort == 'created') { echo ($dir == 'ASC') ? '&uarr;' : '&darr;'; } ?> <?php echo JText::_('Date'); ?>
						</a>
					</th>
					<th scope="col">
						<a<?php if ($sort == 'filename') { echo ' class="active"'; } ?> href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList&sort=filename&dir=' . $altdir); ?>">
							<?php if ($sort == 'filename') { echo ($dir == 'ASC') ? '&uarr;' : '&darr;'; } ?> <?php echo JText::_('Name'); ?>
						</a>
					</th>
					<th scope="col">
						<?php echo JText::_('Preview'); ?>
					</th>
					<th scope="col">
						<?php echo JText::_('Size'); ?>
					</th>
					<th scope="col">
						<a<?php if ($sort == 'created_by') { echo ' class="active"'; } ?> href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList&sort=created_by&dir=' . $altdir); ?>">
							<?php if ($sort == 'created_by') { echo ($dir == 'ASC') ? '&uarr;' : '&darr;'; } ?> <?php echo JText::_('Uploaded by'); ?>
						</a>
					</th>
					<th scope="col">
						<a<?php if ($sort == 'description') { echo ' class="active"'; } ?> href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $this->page->scope . '&pagename=Special:FileList&sort=description&dir=' . $altdir); ?>">
							<?php if ($sort == 'description') { echo ($dir == 'ASC') ? '&uarr;' : '&darr;'; } ?> <?php echo JText::_('Description'); ?>
						</a>
					</th>
				</tr>
			</thead>
			<tbody>
<?php
if ($rows)
{
	jimport('joomla.filesystem.file');

	$dateFormat = '%d %b %Y';
	$tz = 0;
	if (version_compare(JVERSION, '1.6', 'ge'))
	{
		$dateFormat = 'd M Y';
		$tz = true;
	}

	foreach ($rows as $row)
	{
		$fsize = JText::_('(unknown)');
		if (is_file(JPATH_ROOT . DS . trim($this->config->get('filepath', '/site/wiki'), DS) . DS . $row->pageid . DS . $row->filename))
		{
			$fsize = \Hubzero\Utility\Number::formatBytes(filesize(JPATH_ROOT . DS . trim($this->config->get('filepath', '/site/wiki'), DS) . DS . $row->pageid . DS . $row->filename));
		}

		$name = JText::_('(unknown)');
		$xprofile = \Hubzero\User\Profile::getInstance($row->created_by);
		if (is_object($xprofile))
		{
			$name = '<a href="' . JRoute::_('index.php?option=com_members&id=' . $row->created_by) . '">' . $this->escape(stripslashes($xprofile->get('name'))) . '</a>';
		}
?>
				<tr>
					<td>
						<time datetime="<?php echo $row->created; ?>"><?php echo $row->created; ?></time>
					</td>
					<td>
						<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $row->scope . '/' . $row->pagename . '/File:' . $row->filename); ?>">
							<?php echo $this->escape(stripslashes($row->filename)); ?>
						</a>
					</td>
					<td>
						<?php
						if (in_array(strtolower(JFile::getExt($row->filename)), array('png', 'gif', 'jpg', 'jpeg', 'jpe'))) {
						?>
						<a rel="lightbox" href="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $row->scope . '/' . $row->pagename . '/File:' . $row->filename); ?>">
							<img src="<?php echo JRoute::_('index.php?option=' . $this->option . '&scope=' . $row->scope . '/' . $row->pagename . '/File:' . $row->filename); ?>" width="50" alt="<?php echo $this->escape(stripslashes($row->filename)); ?>" />
						</a>
						<?php
						}
						?>
					</td>
					<td>
						<span><?php echo $fsize; ?></span>
					</td>
					<td>
						<?php echo $name; ?>
					</td>
					<td>
						<span><?php echo $this->escape(stripslashes($row->description)); ?></span>
					</td>
				</tr>
<?php
	}
}
else
{
?>
				<tr>
					<td colspan="5">
						<?php echo JText::_('No files found.'); ?>
					</td>
				</tr>
<?php
}
?>
			</tbody>
		</table>
<?php
//$pageNav->setAdditionalUrlParam('gid', $group);
$pageNav->setAdditionalUrlParam('scope', $this->page->scope);
$pageNav->setAdditionalUrlParam('pagename', $this->page->pagename);
$pageNav->setAdditionalUrlParam('sort', $sort);
$pageNav->setAdditionalUrlParam('dir', $dir);

echo $pageNav->getListFooter();
?>
		<div class="clearfix"></div>
	</div>
</form>