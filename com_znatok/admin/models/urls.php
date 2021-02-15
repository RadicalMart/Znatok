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

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

class ZnatokModelUrls extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		// Add the ordering filtering fields whitelist
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'u.id',
				'url', 'u.url',
				'state', 'u.state',
				'created', 'u.created',
				'modified', 'u.modified',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Set search filter state
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information
		$ordering  = empty($ordering) ? 'u.modified' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array('u.*'))
			->from($db->quoteName('#__znatok_urls', 'u'));

		// Filter by search state
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('u.id = ' . (int) substr($search, 3));
			}
			else
			{
				$sql     = array();
				$columns = array('u.url', 'u.data');

				foreach ($columns as $column)
				{
					$sql[] = $db->quoteName($column) . ' LIKE '
						. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				}
				$query->where('(' . implode(' OR ', $sql) . ')');
			}
		}

		// Group by
		$query->group(array('u.id'));

		// Add the list ordering clause
		$ordering  = $this->state->get('list.ordering', 'u.modified');
		$direction = $this->state->get('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getItems()
	{
		if ($items = parent::getItems())
		{
			foreach ($items as $item)
			{
				// Set data
				$item->data = new Registry($item->data);
			}
		}

		return $items;
	}
}