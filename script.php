<?php
/*
 * @package     Znatok Package
 * @subpackage  pkg_znatok
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Septdir Workshop. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

class pkg_znatokInstallerScript
{
	/**
	 * Minimum PHP version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $minimumPhp = '7.0';

	/**
	 * Minimum Joomla version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $minimumJoomla = '3.9.0';

	/**
	 * Minimum MySQL version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $minimumMySQL = '5.7';

	/**
	 * Minimum MariaDb version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $minimumMariaDb = '10.2.3';

	/**
	 * Runs right before any installation action.
	 *
	 * @param   string                           $type    Type of PostFlight action.
	 * @param   InstallerAdapter|PackageAdapter  $parent  Parent object calling object.
	 *
	 * @throws  Exception
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	function preflight($type, $parent)
	{
		// Check compatible
		if (!$this->checkCompatible()) return false;

		if ($type === 'update')
		{
			// Refresh media
			(new Version())->refreshMediaVersion();
		}

		return true;
	}

	/**
	 * Method to check compatible.
	 *
	 * @throws  Exception
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function checkCompatible()
	{
		// Check old Joomla
		if (!class_exists('Joomla\CMS\Version'))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('PKG_ZNATOK_ERROR_COMPATIBLE_JOOMLA',
				$this->minimumJoomla), 'error');

			return false;
		}

		$app = Factory::getApplication();

		// Check PHP
		if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
		{
			$app->enqueueMessage(Text::sprintf('PKG_ZNATOK_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
				'error');

			return false;
		}

		// Check joomla version
		if (!(new Version())->isCompatible($this->minimumJoomla))
		{
			$app->enqueueMessage(Text::sprintf('PKG_ZNATOK_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
				'error');

			return false;
		}

		// Check database version
		$db            = Factory::getDbo();
		$serverType    = $db->getServerType();
		$serverVersion = $db->getVersion();
		if ($serverType == 'mysql' && stripos($serverVersion, 'mariadb') !== false)
		{
			$serverVersion = preg_replace('/^5\.5\.5-/', '', $serverVersion);

			if (!(version_compare($serverVersion, $this->minimumMariaDb) >= 0))
			{
				$app->enqueueMessage(Text::sprintf('PKG_ZNATOK_ERROR_COMPATIBLE_DATABASE',
					$this->minimumMySQL, $this->minimumMariaDb), 'error');

				return false;
			}
		}
		elseif ($serverType == 'mysql' && !(version_compare($serverVersion, $this->minimumMySQL) >= 0))
		{
			$app->enqueueMessage(Text::sprintf('PKG_ZNATOK_ERROR_COMPATIBLE_DATABASE',
				$this->minimumMySQL, $this->minimumMariaDb), 'error');

			return false;
		}

		return true;
	}
}