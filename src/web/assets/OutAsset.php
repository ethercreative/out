<?php

namespace ether\out\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class OutAsset extends AssetBundle
{

	public function init ()
	{
		$this->sourcePath = __DIR__;

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'OutEdit.min.js',
		];

		$this->css = [
			'out.css',
		];

		parent::init();
	}

}