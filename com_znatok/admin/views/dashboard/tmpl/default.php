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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::stylesheet('com_znatok/admin.min.css', array('version' => 'auto', 'relative' => true));

?>
<div>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<ul>
			<?php if ($warnings = ZnatokHelper::getSiteWarnings())
			{
				foreach ($warnings as $key)
				{
					echo '<li>' . Text::_('COM_ZNATOK_SITE_WARNINGS_' . $key) . '</li>';
				}
			} ?>
		</ul>
	</div>
</div>
