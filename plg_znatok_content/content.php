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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

class plgZnatokContent extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  JDatabaseDriver
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to get url params for canonical and redirect.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onZnatokDoublesProtection()
	{
		if ($this->app->input->get('option') === 'com_content')
		{
			$view = $this->app->input->get('view');
			$id   = $this->app->input->getInt('id');
			$link = null;

			JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
			if ($view === 'categories')
			{
				// Content Categories
				$link = 'index.php?option=com_content&view=categories&id=' . $id;
			}
			elseif ($view === 'category')
			{
				// Category
				$link  = ContentHelperRoute::getCategoryRoute($id);
				$limit = $this->getContentCategoryLimit();
				if ($offset = $this->app->input->getInt('start'))
				{
					$link .= '&start=' . floor($offset / $limit) * $limit;
				}
			}
			elseif ($view == 'article')
			{
				// Content Article
				$link = ContentHelperRoute::getArticleRoute($id, $this->app->input->getInt('catid'));
			}

			if ($link) return array(
				'link'              => $link,
				'use_route'         => true,
				'canonical_allowed' => array(),
				'redirect_allowed'  => array()
			);
		}

		return false;
	}

	/**
	 * Method to get content category limit.
	 *
	 * @return int content category page items limit.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getContentCategoryLimit()
	{
		/* @var $app SiteApplication */
		$app    = $this->app;
		$params = $app->getParams();
		if ($app->input->get('layout') === 'blog' || $params->get('layout_type') === 'blog')
		{
			$limit = $params->get('num_leading_articles') + $params->get('num_intro_articles');
		}
		else
		{
			$itemid = $app->input->get('id', 0, 'int') . ':'
				. $app->input->get('Itemid', 0, 'int');
			$limit  = (int) $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.limit',
				'limit', $params->get('display_num'), 'uint');
		}

		return $limit;
	}

	/**
	 * Add pagination meta data.
	 *
	 * @param   string    $context  The context of the content being passed to the plugin.
	 * @param   object   &$row      The item object.
	 * @param   mixed    &$params   The view params.
	 * @param   integer   $page     The 'page' number.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if ($this->app->isClient('site'))
		{
			if ($context === 'com_content.category'
				&& $this->app->input->get('option') === 'com_content'
				&& $this->app->input->get('view') === 'category'
				&& !empty($row->introtext))
			{
				if ($paginationMeta = $this->getPaginationMeta())
				{
					$paginationMeta['items_title'][] = $row->title;
					if (empty($paginationMeta['category_title']))
					{
						$db                               = $this->db;
						$query                            = $db->getQuery(true)
							->select('title')
							->from($db->quoteName('#__categories'))
							->where('id = ' . $this->app->input->getInt('id'));
						$paginationMeta['category_title'] = $db->setQuery($query)->loadResult();
					}
					if (empty($paginationMeta['page']))
					{
						$page = 1;
						if ($offset = $this->app->input->getInt('start'))
						{
							$page += $offset / $this->getContentCategoryLimit();
						}
						$paginationMeta['page'] = $page;
					}

					Factory::getDocument()->addScriptOptions('plg_znatok_pagination_meta', $paginationMeta);
				}
			}
		}
	}

	/**
	 * Method to get content category pagination meta.
	 *
	 * @return array|false Data array on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getPaginationMeta()
	{
		/* @var HtmlDocument $doc */
		$doc = Factory::getDocument();
		if ($doc->getType() !== 'html') return false;

		if (!$data = $doc->getScriptOptions('plg_znatok_pagination_meta'))
		{
			$data = array(
				'page'           => null,
				'category_title' => null,
				'items_title'    => array()
			);

			$doc->addScriptOptions('plg_znatok_pagination_meta', $data);
		}

		return $data;
	}

	/**
	 * Method to get pagination meta data for set.
	 *
	 * @return array|false Data array on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onZnatokPaginationMeta()
	{
		if (
			$this->app->input->get('option') !== 'com_content'
			|| $this->app->input->get('view') !== 'category'
		) return false;

		if ($data = $this->getPaginationMeta())
		{
			if (empty($data['page']) || $data['page'] <= 1) return false;

			/* @var HtmlDocument $doc */
			$doc = Factory::getDocument();
			if ($doc->getType() !== 'html') return false;

			$result = array(
				'title'       => Text::sprintf('COM_ZNATOK_PAGINATION_TITLE', $doc->getTitle(), $data['page']),
				'description' => false,
			);

			// Set description
			$descriptionStart   = $data['category_title'] . ': ';
			$descriptionEnd     = ' ...';
			$descriptionEndShow = false;
			$descriptionTotal   = iconv_strlen($descriptionStart) + iconv_strlen($descriptionEnd);
			$descriptionMax     = 250;

			$descriptionMiddle = array();
			foreach ($data['items_title'] as $title)
			{
				$descriptionTotalNew = $descriptionTotal + iconv_strlen($title) + 2;
				if ($descriptionTotalNew <= $descriptionMax)
				{
					$descriptionTotal    = $descriptionTotalNew;
					$descriptionMiddle[] = $title;
				}
				else
				{
					$descriptionEndShow = true;
					break;
				}
			}

			$result['description'] = $descriptionStart . implode(', ', $descriptionMiddle);
			if ($descriptionEndShow)
			{
				$result['description'] .= $descriptionEnd;
			}

			$doc->addScriptOptions('plg_znatok_pagination_meta', $data);

			return $result;
		}

		return false;
	}
}
