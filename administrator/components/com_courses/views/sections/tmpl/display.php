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

$canDo = CoursesHelper::getActions();

JToolBarHelper::title(JText::_('COM_COURSES') . ': ' . JText::_('Sections'), 'courses.png');
if ($canDo->get('core.create'))
{
	JToolBarHelper::addNew();
}
if ($canDo->get('core.edit'))
{
	JToolBarHelper::editList();
}
if ($canDo->get('core.delete'))
{
	JToolBarHelper::deleteList('delete', 'delete');
}
JToolBarHelper::spacer();
JToolBarHelper::help('sections');

JHTML::_('behavior.tooltip');
?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.getElementById('adminForm');
	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}
	// do field validation
	submitform(pressbutton);
}
</script>

<form action="index.php?option=<?php echo $this->option ?>&amp;controller=<?php echo $this->controller; ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<label for="filter_search"><?php echo JText::_('COM_COURSES_SEARCH'); ?>:</label>
		<input type="text" name="search" id="filter_search" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo JText::_('Search...'); ?>" />

		<input type="submit" value="<?php echo JText::_('COM_COURSES_GO'); ?>" />
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<thead>
			<tr>
				<th colspan="10">
					(<a href="index.php?option=<?php echo $this->option ?>&amp;controller=offerings&amp;course=<?php echo $this->course->get('id'); ?>">
						<?php echo $this->escape(stripslashes($this->course->get('alias'))); ?>
					</a>)
					<a href="index.php?option=<?php echo $this->option ?>&amp;controller=offerings&amp;course=<?php echo $this->course->get('id'); ?>">
						<?php echo $this->escape(stripslashes($this->course->get('title'))); ?>
					</a>:
					<?php echo $this->escape(stripslashes($this->offering->get('title'))); ?>
				</th>
			</tr>
			<tr>
				<th scope="col"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows); ?>);" /></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'ID', 'id', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'Title', 'title', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', JText::_('Alias'), 'alias', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', JText::_('Default'), 'is_default', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
			<?php if ($canDo->get('core.edit.state')) { ?>
				<th scope="col"><?php echo JHTML::_('grid.sort', JText::_('State'), 'state', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
			<?php } ?>
				<th scope="col"><?php echo JHTML::_('grid.sort', JText::_('Starts'), 'start_date', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', JText::_('Ends'), 'end_date', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JText::_('Students'); ?></th>
				<th scope="col"><?php echo JText::_('Codes'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="10"><?php echo $this->pageNav->getListFooter(); ?></td>
			</tr>
		</tfoot>
		<tbody>
<?php
$i = 0;
$k = 0;
$n = $this->rows->total();
foreach ($this->rows as $row)
{
	$tip = '[coming soon]';

	$students = $row->members(array('count' => true, 'student' => 1));

	$allcodes = $row->codes(array('count' => true));
	$redeemed = $row->codes(array('count' => true, 'redeemed' => 1));
?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<input type="checkbox" name="id[]" id="cb<?php echo $i;?>" value="<?php echo $row->get('id'); ?>" onclick="isChecked(this.checked);" />
				</td>
				<td>
					<?php echo $this->escape($row->get('id')); ?>
				</td>
				<td>
				<?php if ($canDo->get('core.edit')) { ?>
					<a href="index.php?option=<?php echo $this->option ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=edit&amp;id[]=<?php echo $row->get('id'); ?>">
						<?php echo $this->escape(stripslashes($row->get('title'))); ?>
					</a>
				<?php } else { ?>
					<span>
						<?php echo $this->escape(stripslashes($row->get('title'))); ?>
					</span>
				<?php } ?>
				</td>
				<td>
				<?php if ($canDo->get('core.edit')) { ?>
					<a href="index.php?option=<?php echo $this->option ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=edit&amp;id[]=<?php echo $row->get('id'); ?>">
						<?php echo $this->escape(stripslashes($row->get('alias'))); ?>
					</a>
				<?php } else { ?>
					<span>
						<?php echo $this->escape(stripslashes($row->get('alias'))); ?>
					</span>
				<?php } ?>
				</td>
				<td>
				<?php if ($canDo->get('core.edit')) { ?>
					<a href="index.php?option=<?php echo $this->option ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=makedefault&amp;id[]=<?php echo $row->get('id'); ?>&amp;offering=<?php echo $this->offering->get('id'); ?>&amp;<?php echo JUtility::getToken(); ?>=1">
					<?php if ($row->get('is_default')) { ?>
						<span class="state yes">
							<span class="text"><?php echo JText::_('yes'); ?></span>
						</span>
					<?php } else { ?>
						<span class="state no">
							<span class="text"><?php echo JText::_('no'); ?></span>
						</span>
					<?php } ?>
					</a>
				<?php } else { ?>
					<span>
						<?php echo $this->escape(stripslashes($row->get('alias'))); ?>
					</span>
				<?php } ?>
				</td>
			<?php if ($canDo->get('core.edit.state')) { ?>
				<td>
					<?php if ($row->get('state') == 1) { ?>
					<a class="jgrid" href="index.php?option=<?php echo $this->option; ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=unpublish&amp;id[]=<?php echo $row->get('id'); ?>&amp;offering=<?php echo $this->offering->get('id'); ?>&amp;<?php echo JUtility::getToken(); ?>=1" title="<?php echo JText::_('Unpublish'); ?>">
						<span class="state publish">
							<span class="text"><?php echo JText::_('Published'); ?></span>
						</span>
					</a>
					<?php } else if ($row->get('state') == 2) { ?>
					<a class="jgrid" href="index.php?option=<?php echo $this->option; ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=publish&amp;id[]=<?php echo $row->get('id'); ?>&amp;offering=<?php echo $this->offering->get('id'); ?>&amp;<?php echo JUtility::getToken(); ?>=1" title="<?php echo JText::_('Restore'); ?>">
						<span class="state trash">
							<span class="text"><?php echo JText::_('Trashed'); ?></span>
						</span>
					</a>
					<?php } else if ($row->get('state') == 3) { ?>
					<a class="jgrid" href="index.php?option=<?php echo $this->option; ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=publish&amp;id[]=<?php echo $row->get('id'); ?>&amp;offering=<?php echo $this->offering->get('id'); ?>&amp;<?php echo JUtility::getToken(); ?>=1" title="<?php echo JText::_('Publish'); ?>">
						<span class="state pending">
							<span class="text"><?php echo JText::_('Draft'); ?></span>
						</span>
					</a>
					<?php } else if ($row->get('state') == 0) { ?>
					<a class="jgrid" href="index.php?option=<?php echo $this->option; ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=publish&amp;id[]=<?php echo $row->get('id'); ?>&amp;offering=<?php echo $this->offering->get('id'); ?>&amp;<?php echo JUtility::getToken(); ?>=1" title="<?php echo JText::_('Publish'); ?>">
						<span class="state unpublish">
							<span class="text"><?php echo JText::_('Unpublished'); ?></span>
						</span>
					</a>
					<?php } ?>
				</td>
			<?php } ?>
				<td>
					<?php echo JHTML::_('date', $row->get('start_date'), JText::_('DATE_FORMAT_HZ1')); ?>
				</td>
				<td>
					<?php echo ($row->get('end_date') && $row->get('end_date') != '0000-00-00 00:00:00') ? JHTML::_('date', $row->get('end_date'), JText::_('DATE_FORMAT_HZ1')) : JText::_('(never)'); ?>
				</td>
				<td>
					<?php if ($canDo->get('core.manage') && $students > 0) { ?>
						<a class="glyph list" href="index.php?option=<?php echo $this->option; ?>&amp;controller=students&amp;offering=<?php echo $row->get('offering_id'); ?>&amp;section=<?php echo $row->get('id'); ?>">
							<?php echo $students; ?>
						</a>
					<?php } else { ?>
						<?php echo $students; ?>
						<?php if ($canDo->get('core.manage')) { ?>
						&nbsp;
						<a class="state add" href="index.php?option=<?php echo $this->option; ?>&amp;controller=students&amp;offering=<?php echo $row->get('offering_id'); ?>&amp;section=<?php echo $row->get('id'); ?>&amp;task=add">
							<span><?php echo JText::_('[ + ]'); ?></span>
						</a>
						<?php } ?>
					<?php } ?>
				</td>
				<td>
					<a class="code" href="index.php?option=<?php echo $this->option; ?>&amp;controller=codes&amp;section=<?php echo $row->get('id'); ?>">
						<span><?php echo JText::sprintf('%s of %s redeemed', $redeemed, $allcodes); ?></span>
					</a>
				</td>
			</tr>
<?php
	$i++;
	$k = 1 - $k;
}
?>
		</tbody>
	</table>

	<input type="hidden" name="offering" value="<?php echo $this->offering->get('id'); ?>" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />

	<input type="hidden" name="filter_order" value="<?php echo $this->filters['sort']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filters['sort_Dir']; ?>" />

	<?php echo JHTML::_('form.token'); ?>
</form>
