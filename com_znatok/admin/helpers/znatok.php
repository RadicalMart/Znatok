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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class ZnatokHelper extends ContentHelper
{
	/**
	 * Configure the linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_ZNATOK_URLS'),
			'index.php?option=com_znatok&view=urls',
			$vName == 'urls');
	}

	/**
	 * Method to get site check result.
	 *
	 * @return array Checks results.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getSiteWarnings()
	{
		$result = array();

		// Check sef plugin
		if ($sef = PluginHelper::getPlugin('system', 'sef'))
		{
			if ((new Registry($sef->params))->get('domain'))
			{
				$result[] = 'sef_plugin_domain';
			}
		}

		// Check content
		$content = ComponentHelper::getParams('com_content');
		if (!$content->get('sef_advanced')) $result[] = 'content_sef_advanced';
		if ($content->get('show_feed_link')) $result[] = 'content_show_feed_link';

		return $result;
	}
}