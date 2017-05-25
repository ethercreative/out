<?php

namespace Craft;

class OutPlugin extends BasePlugin {

	public function getName ()
	{
		return $this->getSettings()->pluginName;
	}

	public function getDescription ()
	{
		return "A reporting and export plugin for Craft CMS";
	}

	public function getVersion ()
	{
		return "0.0.1";
	}

	public function getSchemaVersion ()
	{
		return "0.0.1";
	}

	public function getDeveloper ()
	{
		return "Ether Creative";
	}

	public function getDeveloperUrl ()
	{
		return "https://ethercreative.co.uk";
	}

	public function defineSettings ()
	{
		return [
			"pluginName" => [
				AttributeType::String,
				"default" => "Out"
			],
		];
	}

	public function getSettingsHtml ()
	{
		return craft()->templates->render('out/settings', array(
			'settings' => $this->getSettings()
		));
	}

	public function hasCpSection ()
	{
		return true;
	}

	public function registerCpRoutes ()
	{
		return [
			"out" => ["action" => "out/index"],
			"out/new" => ["action" => "out/createEdit"],
			"out/(?P<reportId>\d+)" => ["action" => "out/createEdit"],
		];
	}

}