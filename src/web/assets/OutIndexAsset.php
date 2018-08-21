<?php

namespace ether\out\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class OutIndexAsset extends AssetBundle
{

	public function init ()
	{
		$this->sourcePath = __DIR__;

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'OutIndex.min.js',
		];

		parent::init();
	}

}