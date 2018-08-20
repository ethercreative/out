<?php

namespace ether\out\web\assets;

use craft\web\AssetBundle;

class OutAsset extends AssetBundle
{

	public function init ()
	{
		$this->sourcePath = __DIR__;

		$this->js = [
			'out.min.js',
		];

		$this->css = [
			'out.css',
		];

		parent::init();
	}

}