<?php
/*
 * @package     Znatok Package
 * @subpackage  plg_znatok_content
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class PlgZnatokContentInstallerScript
{
	/**
	 * External files.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $externalFiles = array(
		array(
			'src'  => JPATH_ROOT . '/plugins/znatok/content/template/categories/home.xml',
			'dest' => JPATH_ROOT . '/templates/system/html/com_content/categories/home.xml',
			'type' => 'file',
		),
	);

	/**
	 * Runs right after any installation action.
	 *
	 * @param   string            $type    Type of PostFlight action. Possible values are:
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @throws  Exception
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	function postflight($type, $parent)
	{
		// Enable plugin
		if ($type == 'install') $this->enablePlugin($parent);

		// Copy external files
		$this->copyExternalFiles($parent->getParent());

		return true;
	}

	/**
	 * Enable plugin after installation.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function enablePlugin($parent)
	{
		// Prepare plugin object
		$plugin          = new stdClass();
		$plugin->type    = 'plugin';
		$plugin->element = $parent->getElement();
		$plugin->folder  = (string) $parent->getParent()->manifest->attributes()['group'];
		$plugin->enabled = 1;

		// Update record
		Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));
	}

	/**
	 * Method to copy  external files.
	 *
	 * @param   Installer  $installer  Installer calling object.
	 *
	 * @return  bool True on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function copyExternalFiles($installer)
	{
		$copyFiles = array();
		foreach ($this->externalFiles as $path)
		{
			$path['src']  = Path::clean($path['src']);
			$path['dest'] = Path::clean($path['dest']);
			if (basename($path['dest']) !== $path['dest'])
			{
				$newdir = dirname($path['dest']);
				if (!Folder::create($newdir))
				{
					Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

					return false;
				}
			}

			$copyFiles[] = $path;
		}

		return $installer->copyFiles($copyFiles, true);
	}

	/**
	 * This method is called after extension is uninstalled.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function uninstall($parent)
	{
		// Remove external files
		$this->removeExternalFiles();
	}

	/**
	 * Method to remove external files.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function removeExternalFiles()
	{
		foreach ($this->externalFiles as $path)
		{
			$path['dest'] = Path::clean($path['dest']);
			if ($path['type'] === 'file' && File::exists($path['dest'])) File::delete($path['dest']);
			elseif ($path['type'] === 'folder' && Folder::exists($path['dest'])) Folder::delete($path['dest']);
		}
	}
}