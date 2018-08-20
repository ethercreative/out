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
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use ether\out\elements\db\ExportQuery;


/**
 * Class Export
 *
 * @author  Ether Creative
 * @package ether\out\elements
 * @since   1.0.0
 */
class Export extends Element
{

	// Properties
	// =========================================================================

	/** @var string */
	public $elementType;

	/** @var string */
	public $elementSource;

	/** @var string|null */
	public $search = null;

	/** @var int|null */
	public $limit = null;

	/** @var \DateTime|null */
	public $startDate = null;

	/** @var \DateTime|null */
	public $endDate = null;

	/** @var array */
	public $fieldSettings = [];

	/** @var int */
	public $fieldLayoutId;

	// Craft
	// =========================================================================

	public static function hasTitles (): bool
	{
		return true;
	}

	public static function hasContent (): bool
	{
		return false;
	}

	public static function hasStatuses (): bool
	{
		return false;
	}

	public static function isLocalized (): bool
	{
		return false;
	}

	/**
	 * @param bool $isNew
	 *
	 * @throws \yii\db\Exception
	 * @throws \yii\base\Exception
	 */
	public function afterSave (bool $isNew)
	{
		if ($isNew)
		{
			\Craft::$app->db->createCommand()->insert(
				'{{%out_exports}}',
				array_merge($this->_map(), ['id' => $this->id])
			)->execute();
		}
		else
		{
			\Craft::$app->db->createCommand()->update(
				'{{%out_exports}}',
				$this->_map(),
				['id' => $this->id]
			)->execute();
		}

		parent::afterSave($isNew);
	}

	public static function find (): ElementQueryInterface
	{
		return new ExportQuery(static::class);
	}

	public function getCpEditUrl (): string
	{
		return UrlHelper::cpUrl('out/' . $this->id);
	}

	public function getFieldLayout ()
	{
		if (!$this->fieldLayoutId)
			return new FieldLayout();

		return parent::getFieldLayout();
	}

	protected static function defineTableAttributes (): array
	{
		return [
			'id' => ['label' => \Craft::t('app', 'ID')],
			'dateCreated' => ['label' => \Craft::t('app', 'Date Created')],
			'dateUpdated' => ['label' => \Craft::t('app', 'Date Updated')],
		];
	}

	protected static function defineDefaultTableAttributes (string $source): array
	{
		$attrs   = [];

		$attrs[] = 'id';
		$attrs[] = 'dateCreated';
		$attrs[] = 'dateUpdated';

		return $attrs;
	}

	public static function sortOptions (): array
	{
		return [
			'id'          => \Craft::t('app', 'ID'),
			'dateCreated' => \Craft::t('app', 'Date Created'),
			'dateUpdated' => \Craft::t('app', 'Date Updated'),
		];
	}

	public static function sources (string $context = null): array
	{
		return [
			'*' => [
				'key'   => '*',
				'label' => \Craft::t('out', 'All Exports'),
			],
		];
	}

	// Helpers
	// =========================================================================

	private function _map ()
	{
		return [
			'title'         => $this->title,
			'elementType'   => $this->elementType,
			'elementSource' => $this->elementSource,
			'search'        => $this->search,
			'limit'         => $this->limit,
			'startDate'     => $this->startDate,
			'endDate'       => $this->endDate,
			'fieldSettings' => $this->fieldSettings,
			'fieldLayoutId' => $this->fieldLayoutId,
		];
	}

}