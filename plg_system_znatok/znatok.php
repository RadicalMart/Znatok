<?php
/*
 * @package     Znatok Package
 * @subpackage  plg_system_znatok
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgSystemZnatok extends CMSPlugin
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
	 * Znatok component params.
	 *
	 * @var  Registry
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $componentParams = null;

	/**
	 * No doubles canonical function enable.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $doubles_canonical = false;

	/**
	 * No doubles redirect function enable.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $doubles_redirect = false;

	/**
	 * Pagination title function enable.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $pagination_title = false;

	/**
	 * Pagination description function enable.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $pagination_description = false;

	/**
	 * Page canonical link.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $canonical = null;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array    $config   An optional associative array of configuration settings.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Set component params
		$this->componentParams = ComponentHelper::getParams('com_znatok');

		// Load component languages
		Factory::getLanguage()->load('com_znatok');

		// Set functions status
		$this->doubles_canonical      = ($this->componentParams->get('doubles_canonical', 0)) ? true : false;
		$this->doubles_redirect       = ($this->componentParams->get('doubles_redirect', 0)) ? true : false;
		$this->pagination_title       = ($this->componentParams->get('pagination_title', 0)) ? true : false;
		$this->pagination_description = ($this->componentParams->get('pagination_description', 0)) ? true : false;

		// Import the znatok plugins
		PluginHelper::importPlugin('znatok');
	}

	/**
	 * Doubles protection.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterRoute()
	{
		// No doubles protection
		if ($this->doubles_canonical || $this->doubles_redirect) $this->fixDoubles();
	}

	/**
	 * Method to check page url, and redirect if link is not correct.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function fixDoubles()
	{
		if ($this->app->isClient('site') && ($this->doubles_canonical || $this->doubles_redirect))
		{
			$link              = null;
			$canonical_allowed = ArrayHelper::getColumn(
				ArrayHelper::fromObject($this->componentParams->get('doubles_canonical_allowed', new stdClass())),
				'key');
			$redirect_allowed  = ArrayHelper::getColumn(
				ArrayHelper::fromObject($this->componentParams->get('doubles_redirect_allowed', new stdClass())),
				'key');
			foreach ($this->app->triggerEvent('onZnatokDoublesProtection') as $result)
			{
				if (empty($result)) continue;

				if (!empty($result['link']))
				{
					$link = (!empty($result['use_route'])) ? Route::_($result['link'], false) : $result['link'];
				}
				if (!empty($result['canonical_allowed']))
				{
					$canonical_allowed = array_merge($canonical_allowed, $result['canonical_allowed']);
				}
				if (!empty($result['redirect_allowed']))
				{
					$redirect_allowed = array_merge($redirect_allowed, $result['redirect_allowed']);
				}
			}
			if ($link)
			{
				$uri       = Uri::getInstance();
				$canonical = Uri::getInstance($link);

				// Add allowed variables from params
				foreach ($canonical_allowed as $name)
				{
					if ($var = $uri->getVar($name, false)) $canonical->setVar($name, $var);
				}

				// Check empty canonical variables
				foreach ($canonical->getQuery(true) as $name => $value)
				{
					$value = trim($value);
					if (empty($value)) $canonical->delVar($name);
				}

				// Set global canonical variable
				if ($this->doubles_canonical)
				{
					$this->canonical = $uri->toString(array('scheme', 'host', 'port'))
						. $canonical->toString(array('path', 'query', 'fragment'));
				}

				// Prepare redirect link
				if ($this->doubles_redirect)
				{
					// Add others variable
					foreach ($uri->getQuery(true) as $name => $value)
					{
						$value = trim($value);
						if (empty($value)) continue;

						// Add utm variables
						if (preg_match('#^utm_#', $name)) $canonical->setVar($name, $value);

						// Add allowed variables from params
						if (in_array($name, $redirect_allowed)) $canonical->setVar($name, $value);
					}

					// Redirect if need
					$current  = $uri->toString(array('path', 'query', 'fragment'));
					$redirect = $canonical->toString(array('path', 'query', 'fragment'));
					if (urldecode($current) != urldecode($redirect)) $this->app->redirect($redirect, 301);
				}
			}
		}
	}

	/**
	 * Set canonical and pagination meta.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onBeforeCompileHead()
	{
		if ($this->doubles_canonical) $this->setCanonical();
		if ($this->pagination_title || $this->pagination_description) $this->setPaginationMeta();
	}

	/**
	 * Method to set correct canonical link to page.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function setCanonical()
	{
		if ($this->app->isClient('site') && $this->canonical)
		{
			/* @var HtmlDocument $doc */
			$doc = Factory::getDocument();
			if ($doc->getType() === 'html')
			{
				foreach ($doc->_links as $url => $link)
				{
					if (isset($link['relation']) && $link['relation'] === 'canonical') unset($doc->_links[$url]);
				}
				$doc->addHeadLink(htmlspecialchars($this->canonical), 'canonical');
			}
		}
	}

	/**
	 * Method to set pagination meta.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function setPaginationMeta()
	{
		if ($this->app->isClient('site'))
		{
			/* @var HtmlDocument $doc */
			$doc = Factory::getDocument();
			if ($doc->getType() === 'html')
			{
				$title       = false;
				$description = false;
				foreach ($this->app->triggerEvent('onZnatokPaginationMeta') as $result)
				{
					if (isset($result['title']) && $result['title'] !== false) $title = $result['title'];
					if (isset($result['description']) && $result['description'] !== false)
					{
						$description = $result['description'];
					}
				}

				if ($title && $this->pagination_title) $doc->setTitle($title);
				if ($description !== false && $this->pagination_description) $doc->setDescription($description);
			}
		}
	}
}