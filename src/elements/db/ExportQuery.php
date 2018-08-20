<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\elements\db;

use craft\elements\db\ElementQuery;


/**
 * Class ExportQuery
 *
 * @author  Ether Creative
 * @package ether\out\elements\db
 * @since   1.0.0
 */
class ExportQuery extends ElementQuery
{

	protected function beforePrepare (): bool
	{
		$this->joinElementTable('out_exports');

		$this->query->select([
			'out_exports.title',
			'out_exports.elementType',
			'out_exports.elementSource',
			'out_exports.search',
			'out_exports.limit',
			'out_exports.startDate',
			'out_exports.endDate',
			'out_exports.fieldSettings',
			'out_exports.fieldLayoutId',
		]);

		return parent::beforePrepare();
	}

}