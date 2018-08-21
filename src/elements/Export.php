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
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use ether\out\elements\db\ExportQuery;
use ether\out\Out;
use yii\helpers\Inflector;


/**
 * Class Export
 *
 * @property array $fieldSettings
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
	public $order = null;

	/** @var string|null */
	public $search = null;

	/** @var int|null */
	public $limit = null;

	/** @var \DateTime|null */
	public $startDate = null;

	/** @var \DateTime|null */
	public $endDate = null;

	/** @var string|array */
	public $_fieldSettings = [];

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

		return parent::afterSave($isNew);
	}

	public static function find (): ElementQueryInterface
	{
		return new ExportQuery(static::class);
	}

	public function getCpEditUrl (): string
	{
		if (self::_canCreate())
			return UrlHelper::cpUrl('out/' . $this->id);

		return '';
	}

	public function getFieldLayout ()
	{
		if (!$this->fieldLayoutId)
			return new FieldLayout();

		return parent::getFieldLayout();
	}

	protected static function defineTableAttributes (): array
	{
		$attrs = [
			'title'       => ['label' => \Craft::t('app', 'Title')],
			'dateCreated' => ['label' => \Craft::t('app', 'Date Created')],
			'dateUpdated' => ['label' => \Craft::t('app', 'Date Updated')],
		];

		if (self::_canDownload())
			$attrs['dl'] = ['label' => 'Download'];

		return $attrs;
	}

	protected static function defineDefaultTableAttributes (string $source): array
	{
		$attrs   = [];

		$attrs[] = 'title';
		$attrs[] = 'dateCreated';
		$attrs[] = 'dateUpdated';

		if (self::_canDownload())
			$attrs[] = 'dl';

		return $attrs;
	}

	protected function tableAttributeHtml (string $attribute): string
	{
		if ($attribute === 'dl' && self::_canDownload())
		{
			$icon = '<svg height="19px" version="1.1" viewBox="0 0 14 19" width="14px" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd" stroke="none" stroke-width="1"><g fill="#0d78f2" transform="translate(-383.000000, -213.000000)"><g transform="translate(383.000000, 213.500000)"><path d="M14,6 L10,6 L10,0 L4,0 L4,6 L0,6 L7,13 L14,6 L14,6 Z M0,15 L0,17 L14,17 L14,15 L0,15 L0,15 Z" /></g></g></g></svg>';

			$dl = UrlHelper::cpUrl('out/dl/' . $this->id . '?site=');

			$sites = \Craft::$app->sites->getAllSites();

			if (count($sites) === 1)
				return '<a href="' . $dl . $sites[0]->id . '" title="Download">' . $icon . '</a>';

			$actions = '';

			foreach ($sites as $site)
				$actions .= '<li><a href="' . $dl . $site->id . '">' . $site->name . '</a></li>';

			return <<<HTML
<a data-out-dl>$icon</a>
<div class="menu" data-align="center">
	<ul>
		$actions
	</ul>
</div>
HTML;
		}

		return parent::tableAttributeHtml($attribute);
	}

	public static function sortOptions (): array
	{
		return [
			'title'       => \Craft::t('app', 'Title'),
			'dateCreated' => \Craft::t('app', 'Date Created'),
			'dateUpdated' => \Craft::t('app', 'Date Updated'),
		];
	}

	public static function sources (string $context = null): array
	{
		$name = Inflector::pluralize(Out::getInstance()->getSettings()->exportName);

		return [
			'*' => [
				'key'   => '*',
				'label' => 'All ' . $name,
			],
		];
	}

	// Getters / Setters
	// =========================================================================

	public function setFieldSettings ($value)
	{
		$this->_fieldSettings = Json::decodeIfJson($value);
	}

	public function getFieldSettings (): array
	{
		if (is_array($this->_fieldSettings))
			return $this->_fieldSettings;

		return $this->_fieldSettings = Json::decode($this->_fieldSettings);
	}

	// Helpers
	// =========================================================================

	private function _map ()
	{
		return [
			'title'         => $this->title,
			'elementType'   => $this->elementType,
			'elementSource' => $this->elementSource,
			'order'         => $this->order,
			'search'        => $this->search,
			'limit'         => $this->limit,
			'startDate'     => $this->startDate,
			'endDate'       => $this->endDate,
			'fieldSettings' => $this->fieldSettings,
		];
	}

	private static function _canCreate ()
	{
		return (
			\Craft::$app->user->can('out_createExport')
			|| \Craft::$app->user->getIsAdmin()
		);
	}

	private static function _canDownload ()
	{
		return (
			\Craft::$app->user->can('out_createExport')
			|| \Craft::$app->user->can('out_downloadExport')
			|| \Craft::$app->user->getIsAdmin()
		);
	}

}