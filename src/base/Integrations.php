<?php

namespace ether\out\base;

use Composer\Autoload\ClassMapGenerator;

class Integrations
{

	// Properties
	// =========================================================================

	private static $_integrations;

	// Methods
	// =========================================================================

	public static function register ()
	{
		/** @var IntegrationInterface $integration */
		foreach (self::_getIntegrations() as $integration)
			if ($integration::isInstalled())
				$integration::register();
	}

	public static function fields ()
	{
		$fields = [];

		/** @var IntegrationInterface $integration */
		foreach (self::_getIntegrations() as $integration)
			if ($integration::isInstalled())
				$fields = array_merge($fields, $integration::fields());

		return $fields;
	}

	// Helpers
	// =========================================================================

	private static function _getIntegrations ()
	{
		if (self::$_integrations)
			return self::$_integrations;

		$classes = array_keys(
			ClassMapGenerator::createMap(__DIR__ . '/../integrations')
		);

		return self::$_integrations = $classes;
	}

}