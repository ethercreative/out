<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;


/**
 * Class ExportQuery
 *
 * @author  Ether Creative
 * @package ether\out\elements\db
 * @since   1.0.0
 */
class ExportQuery extends ElementQuery
{

	public $elementType;

	public function elementType ($value)
	{
		$this->elementType = $value;

		return $this;
	}

	protected function beforePrepare (): bool
	{
		// join in the products table
		$this->joinElementTable('out_exports');

		// select the elementType column
		$this->query->select(['out_exports.elementType']);

		if ($this->elementType)
		{
			$this->subQuery->andWhere(
				Db::parseParam('products.elementType', $this->elementType)
			);
		}

		return parent::beforePrepare();
	}

}