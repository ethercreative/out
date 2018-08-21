<?php

namespace ether\out\models;

use craft\base\Model;

class Settings extends Model
{

	public $pluginName = 'Out';
	public $exportName = 'Export';
	public $split = 100;

	public function rules ()
	{
		return [
			[['pluginName', 'exportName'], 'string'],
			[['split'], 'number'],
		];
	}

}