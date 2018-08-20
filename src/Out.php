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
	public $hasCpSettings = false;
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
		$event->rules['out/new'] = 'out/out/edit';
		$event->rules['out/<exportId:\d+>'] = 'out/out/edit';
		$event->rules['out/dl/<exportId:\d+>'] = 'out/out/dl';
	}

}