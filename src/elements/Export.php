<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\elements;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use ether\out\elements\db\ExportQuery;


/**
 * Class Export
 *
 * FIXME: This will create an element TYPE rather than the element itself, I think
 *
 * @author  Ether Creative
 * @package ether\out\elements
 * @since   1.0.0
 */
class Export extends Element
{

	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $elementType;

	/**
	 * @var array
	 */
	public $filter;

	// Craft
	// =========================================================================

	public static function hasTitles (): bool
	{
		return true;
	}

	/**
	 * @param bool $isNew
	 *
	 * @throws \yii\db\Exception
	 */
	public function afterSave (bool $isNew)
	{
		if ($isNew)
		{
			\Craft::$app->db->createCommand()->insert('{{%out_exports}}', [
				'id'          => $this->id,
				'elementType' => $this->elementType,
				'filter'      => $this->filter,
            ])->execute();
		}
		else
		{
			\Craft::$app->db->createCommand()->update('{{%out_exports}}', [
				'elementType' => $this->elementType,
				'filter'      => $this->filter,
            ], ['id' => $this->id])->execute();
		}

		parent::afterSave($isNew);
	}

	public static function find (): ElementQueryInterface
	{
		return new ExportQuery(static::class);
	}

}