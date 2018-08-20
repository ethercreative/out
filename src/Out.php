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
	}

	// Events
	// =========================================================================

	public function onRegisterCpUrlRules (RegisterUrlRulesEvent $event)
	{
		$event->rules['out'] = 'out/out/index';
		$event->rules['out/new'] = 'out/out/edit';
		$event->rules['out/<exportId:\d+>'] = 'out/out/edit';
		$event->rules['out/dl/<exportId:\d+>'] = 'out/out/dl';
	}

	// Craft
	// =========================================================================

	public function getCpNavItem ()
	{
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