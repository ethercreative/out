<?php

namespace ether\out\integrations;

use barrelstrength\sproutforms\elements\Entry;
use barrelstrength\sproutforms\elements\Form;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use ether\out\base\IntegrationInterface;
use yii\base\Event;

class SproutFormsIntegration implements IntegrationInterface
{

	public static function register ()
	{
		Event::on(
			Elements::class,
			Elements::EVENT_REGISTER_ELEMENT_TYPES,
			function (RegisterComponentTypesEvent $event) {
				$event->types[] = Form::class;
				$event->types[] = Entry::class;
			}
		);
	}

	public static function fields (): array
	{
		$fields = [];

		$fields[Form::class] = function (Form $element) {
			return $element->getFields();
		};

		$fields[Entry::class] = function (Entry $element) {
			return $element->getFields();
		};

		return $fields;
	}

	public static function isInstalled (): bool
	{
		return \Craft::$app->plugins->isPluginInstalled('sprout-forms');
	}

}