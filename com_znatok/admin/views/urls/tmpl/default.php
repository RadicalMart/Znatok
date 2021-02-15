<?php
/*
 * @package     Znatok Package
 * @subpackage  com_znatok
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tabstate');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::stylesheet('com_znatok/admin.min.css', array('version' => 'auto', 'relative' => true));

$user      = Factory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$columns = 8;

if ($warnings = ZnatokHelper::getSiteWarnings())
{
	foreach ($warnings as $key)
	{
		Factory::getApplication()->enqueueMessage(Text::_('COM_ZNATOK_SITE_WARNINGS_' . $key), 'warning');
	}
}
?>
<form action="<?php echo Route::_('index.php?option=com_znatok&view=urls'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table id="urlsList" class="table table-striped">
				<thead>
				<tr>
					<th width="1%" class="center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th class="nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_ZNATOK_URL', 'u.url',
							$listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="center nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_ZNATOK_DATE_CREATED', 'u.created',
							$listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="center nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_ZNATOK_DATE_MODIFIED', 'u.modified',
							$listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'u.id',
							$listDirn, $listOrder); ?>
					</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="<?php echo $columns; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$canEdit = $user->authorise('core.edit', 'com_znatok.url.' . $item->id);
					$canChange = $user->authorise('core.edit.state', 'com_znatok.url.' . $item->id);
					?>
					<tr class="row<?php echo $i % 2; ?>" item-id="<?php echo $item->id ?>">
						<td class="center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td>
							<div class="nowrap">
								<?php if ($canEdit) : ?>
									<a class="hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
									   href="<?php echo Route::_('index.php?option=com_znatok&task=url.edit&id='
										   . $item->id); ?>">
										<?php echo $item->url; ?>
									</a>
								<?php else : ?>
									<?php echo $item->url; ?>
								<?php endif; ?>
							</div>
						</td>
						<td class="hidden-phone center nowrap">
							<?php echo (!$item->created) ? '-'
								: Factory::getDate($item->created)->format(Text::_('DATE_FORMAT_LC2')); ?>
						</td>
						<td class="hidden-phone center nowrap">
							<?php echo (!$item->modified) ? '-'
								: Factory::getDate($item->modified)->format(Text::_('DATE_FORMAT_LC2')); ?>
						</td>
						<td class="hidden-phone center">
							<?php echo $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>