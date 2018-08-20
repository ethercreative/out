<?php

namespace ether\out\models;

use craft\base\Model;

class Settings extends Model
{

	public $pluginName = 'Out';
	public $exportName = 'Export';

	public function rules ()
	{
		return [
			[['pluginName', 'exportName'], 'string'],
		];
	}

}