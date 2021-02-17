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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class ZnatokViewDashboard extends HtmlView
{
	/**
	 * View sidebar.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public $sidebar;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		// Add title and toolbar
		$this->addToolbar();

		// Prepare sidebar
		ZnatokHelper::addSubmenu('dashboard');
		$this->sidebar = JHtmlSidebar::render();

		// Check for errors
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode('\n', $errors), 500);
		}

		return parent::display($tpl);
	}

	/**
	 * Add title and toolbar.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function addToolbar()
	{
		$canDo = ZnatokHelper::getActions('com_znatok', 'dashboard');

		// Set page title
		ToolbarHelper::title(Text::_('COM_ZNATOK') . ': ' . Text::_('COM_ZNATOK_DASHBOARD'), 'lamp');


		// Add preferences button
		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_znatok');
		}
	}
}