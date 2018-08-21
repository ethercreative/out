<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out;

use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use ether\out\models\Settings;
use ether\out\services\OutService;
use yii\base\Event;


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
	}

	// Events
	// =========================================================================

	public function onRegisterCpUrlRules (RegisterUrlRulesEvent $event)
	{
		$user = \Craft::$app->user;

		if ($user->can('accessOut') || $user->getIsAdmin())
			$event->rules['out'] = 'out/out/index';

		if ($user->can('out_createExport') || $user->getIsAdmin())
		{
			$event->rules['out/new']               = 'out/out/edit';
			$event->rules['out/<exportId:\d+>']    = 'out/out/edit';
		}

		if (
			$user->can('out_createExport')
			|| $user->can('out_downloadExport')
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

	// Craft
	// =========================================================================

	public function getCpNavItem ()
	{
		$user = \Craft::$app->user;
		if (!$user->can('accessOut') && !$user->getIsAdmin())
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
	 * @return null|string
	 * @throws \Twig_Error_Loader
	 * @throws \yii\base\Exception
	 */
	protected function settingsHtml ()
	{
		return \Craft::$app->getView()->renderTemplate(
			'out/settings', [
			'settings' => $this->getSettings()
		]);
	}

}