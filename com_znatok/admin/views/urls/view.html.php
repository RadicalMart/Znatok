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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;

class ZnatokViewUrls extends HtmlView
{
	/**
	 * Model state variables.
	 *
	 * @var  CMSObject
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * An array of items.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $items;

	/**
	 * Pagination object.
	 *
	 * @var  Pagination
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $pagination;

	/**
	 * Form object for search filters.
	 *
	 * @var  Form
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public $filterForm;

	/**
	 * The active search filters.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public $activeFilters;

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
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Add title and toolbar
		$this->addToolbar();

		// Prepare sidebar
		ZnatokHelper::addSubmenu('urls');
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
		$canDo = ZnatokHelper::getActions('com_znatok', 'urls');

		// Set page title
		ToolbarHelper::title(Text::_('COM_ZNATOK') . ': ' . Text::_('COM_ZNATOK_URLS'), 'lamp');

		if ($canDo->get('core.delete'))
		{
			// Add archive delete
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'urls.delete');
		}

		// Add preferences button
		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_znatok');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by.
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getSortFields()
	{
		return array(
			'u.state'    => Text::_('JSTATUS'),
			'u.id'       => Text::_('JGRID_HEADING_ID'),
			'u.url'      => Text::_('COM_ZNATOK_URL'),
			'u.created'  => Text::_('COM_ZNATOK_DATE_CREATED'),
			'u.modified' => Text::_('COM_ZNATOK_DATE_MODIFIED'),
		);
	}
}