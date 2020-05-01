<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out;

use Cassandra\Set;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use ether\out\base\Integrations;
use ether\out\elements\Export;
use ether\out\models\Settings;
use ether\out\services\OutService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig_Error_Loader;
use yii\base\Event;
use yii\base\Exception;


/**
 * @property OutService $out
 *
 * @author  Ether Creative
 * @package ether\out
 * @since   1.0.0
 */
class Out extends Plugin
{

	// Properties
	// =========================================================================

	public $schemaVersion = '1.0.0';
	public $hasCpSettings = true;
	public $hasCpSection  = true;

	// Initialize
	// =========================================================================

	public function init ()
	{
		parent::init();

		$this->setComponents([
			'out' => OutService::class,
		]);

		// Events
		// ---------------------------------------------------------------------

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			[$this, 'onRegisterCpUrlRules']
		);

		Event::on(
			UserPermissions::class,
			UserPermissions::EVENT_REGISTER_PERMISSIONS,
			[$this, 'onRegisterUserPermissions']
		);

		Event::on(
			Elements::class,
			Elements::EVENT_REGISTER_ELEMENT_TYPES,
			[$this, 'onRegisterElementTypes']
		);

		// Integrations
		// ---------------------------------------------------------------------

		$request = Craft::$app->request;
		if ($request->isCpRequest && strpos($request->url, 'out') !== false)
			Integrations::register();

	}

	// Events
	// =========================================================================

	public function onRegisterCpUrlRules (RegisterUrlRulesEvent $event)
	{
		$user = Craft::$app->user;

		if ($user->checkPermission('accessOut') || $user->getIsAdmin())
			$event->rules['out'] = 'out/out/index';

		if ($user->checkPermission('out_createExport') || $user->getIsAdmin())
		{
			$event->rules['out/new']               = 'out/out/edit';
			$event->rules['out/<exportId:\d+>']    = 'out/out/edit';
		}

		if (
			$user->checkPermission('out_createExport')
			|| $user->checkPermission('out_downloadExport')
			|| $user->getIsAdmin()
		) {
			$event->rules['out/dl/<exportId:\d+>'] = 'out/out/dl';
		}
	}

	public function onRegisterUserPermissions (RegisterUserPermissionsEvent $event)
	{
		$event->permissions['Out'] = [
			'accessOut' => [
				'label' => 'Access Out',
			],
			'out_createExport' => [
				'label' => 'Create Exports',
			],
			'out_downloadExport' => [
				'label' => 'Download Exports',
			],
		];
	}

	public function onRegisterElementTypes (RegisterComponentTypesEvent $event)
	{
		$event->types[] = Export::class;
	}

	// Craft
	// =========================================================================

	public function getCpNavItem ()
	{
		$user = Craft::$app->user;
		if (!($user->checkPermission('accessOut') || $user->getIsAdmin()))
			return null;

		$item = parent::getCpNavItem();

		$item['label'] = $this->getSettings()->pluginName;

		return $item;
	}

	// Settings
	// -------------------------------------------------------------------------

	protected function createSettingsModel ()
	{
		return new Settings();
	}

	/**
	 * @return bool|Model|null|Settings
	 */
	public function getSettings ()
	{
		return parent::getSettings();
	}

	/**
	 * @return null|string
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	protected function settingsHtml ()
	{
		return Craft::$app->getView()->renderTemplate(
			'out/settings', [
			'settings' => $this->getSettings()
		]);
	}

}