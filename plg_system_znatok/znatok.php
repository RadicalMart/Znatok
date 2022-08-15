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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
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
	 * Change com_content forms trigger.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		$formName = $form->getName();
		if ($formName === 'com_config.component' && $this->app->input->get('component') === 'com_znatok')
		{
			Factory::getDocument()->addStyleDeclaration("#global .subform-repeatable {max-width: 300px}");
		}
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
			$canonical         = null;
			$redirect          = null;
			$canonical_allowed = ArrayHelper::getColumn(
				ArrayHelper::fromObject($this->componentParams->get('doubles_canonical_allowed', new stdClass())),
				'key');
			$redirect_allowed  = ArrayHelper::getColumn(
				ArrayHelper::fromObject($this->componentParams->get('doubles_redirect_allowed', new stdClass())),
				'key');
			foreach ($this->app->triggerEvent('onZnatokDoublesProtection', array($this->componentParams)) as $result)
			{
				if (empty($result)) continue;

				// Set canonical
				if (!empty($result['canonical']))
				{
					$canonical = (!empty($result['use_route'])) ? Route::_($result['canonical'], false) : $result['canonical'];
				}
				if (!empty($result['canonical_allowed']))
				{
					$canonical_allowed = array_merge($canonical_allowed, $result['canonical_allowed']);
				}

				// Set redirect
				if (!empty($result['redirect']))
				{
					$redirect = (!empty($result['use_route'])) ? Route::_($result['redirect'], false) : $result['redirect'];
				}
				if (!empty($result['redirect_allowed']))
				{
					$redirect_allowed = array_merge($redirect_allowed, $result['redirect_allowed']);
				}
			}

			// Prepare allowed params
			$canonical_allowed = array_unique($canonical_allowed);
			$redirect_allowed  = array_unique($redirect_allowed);
			if (!empty($canonical_allowed))
			{
				$redirect_allowed = array_unique(array_merge($canonical_allowed, $redirect_allowed));
			}

			if ($canonical || $redirect)
			{
				$uri = Uri::getInstance();
				if ($this->doubles_canonical && $canonical)
				{
					$canonical = Uri::getInstance($canonical);

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
						$canonical = urldecode($uri->toString(array('scheme', 'host', 'port'))
							. $canonical->toString(array('path', 'query', 'fragment')));
						if ($this->app->triggerEvent('onZnatokCanonicalPrepare',
							array($this->componentParams, &$canonical)))
						{
							$canonical = urldecode($canonical);
						}
						$this->canonical = $canonical;
					}
				}

				// Prepare redirect link
				if ($this->doubles_redirect && $redirect)
				{
					$redirect = Uri::getInstance($redirect);

					// Add others variable
					foreach ($uri->getQuery(true) as $name => $value)
					{
						$value = trim($value);
						if (empty($value)) continue;

						// Add utm variables
						if (preg_match('#^utm_#', $name)) $redirect->setVar($name, $value);
						if (preg_match('#^UTM_#', $name)) $redirect->setVar($name, $value);

						// Add Yandex.Metrika debug
						if ($name === '_ym_debug' && $value == 1) $redirect->setVar($name, $value);

						// Add allowed variables from params
						if (in_array($name, $redirect_allowed)) $redirect->setVar($name, $value);
					}

					// Redirect if need
					$current = urldecode($uri->toString(array('path', 'query', 'fragment')));
					$redirect = urldecode($redirect->toString(array('path', 'query', 'fragment')));

					if ($this->app->triggerEvent('onZnatokRedirectPrepare',
						array($this->componentParams, &$redirect, &$current)))
					{
						$redirect = urldecode($redirect);
						$current  = urldecode($current);
					}

					if ($current != $redirect) $this->app->redirect($redirect, 301);
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