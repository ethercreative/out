<?php

namespace ether\out\services;

use craft\base\Component;
use craft\base\Element;
use craft\base\Field;
use craft\elements\db\ElementQuery;
use craft\helpers\StringHelper;
use ether\out\base\Integrations;
use ether\out\elements\Export;
use ether\out\Out;
use ZipArchive;

class OutService extends Component
{

	private $_fields;

	public function fields ()
	{
		$fields = [
			'title'       => ['name' => 'Title', 'handle' => 'title'],
			'dateCreated' => [
				'name'   => 'Date Created',
				'handle' => 'dateCreated'
			],
			'dateUpdated' => [
				'name'   => 'Date Updated',
				'handle' => 'dateUpdated'
			],
		];

		/** @var Field $field */
		foreach (\Craft::$app->fields->getAllFields() as $field)
			$fields[$field->id] = [
				'name'   => $field->name,
				'handle' => $field->handle,
			];

		return $fields;
	}

	public function fieldsFromElementAndSource ($element, string $source)
	{
		$integrations = Integrations::fields();

		if (!array_key_exists($element, $integrations))
			return $this->fields();

		/** @var Element $el */
		$el = new $element;

		$criteria = null;

		foreach ($el->sources() as $src)
			if (array_key_exists('key', $src) && $src['key'] === $source)
				$criteria = $src['criteria'];

		if (!$criteria)
			return [];

		$query = $el::find();

		\Craft::configure($query, $criteria);

		$firstElement = $query->one();

		if (!$firstElement)
			return [];

		return $integrations[$element]($firstElement);
	}

	/**
	 * @param Export $export
	 * @param int    $siteId
	 *
	 * @throws \yii\base\ExitException
	 */
	public function generate (Export $export, int $siteId)
	{
		/** @var Element $element */
		$element = new $export->elementType;

		// Build the criteria
		$criteria = [];

		foreach ($element->sources() as $source)
		{
			if (
				array_key_exists('key', $source)
			    && $source['key'] === $export->elementSource
			) {
				$criteria = $source['criteria'];
				break;
			}
		}

		if (!empty($export->order))
			$criteria['orderBy'] = $export->order;

		if (!empty($export->search))
			$criteria['search'] = $export->search;

		if (!empty($export->limit))
			$criteria['limit'] = $export->limit;

		if (!empty($export->startDate))
			$criteria['after'] = $export->startDate;

		if (!empty($export->endDate))
			$criteria['before'] = $export->endDate;

		/** @var ElementQuery $query */
		$query = $element::find()->siteId($siteId);

		\Craft::configure($query, $criteria);

		// Get the fields
		$this->_fields = $this->fields();
		$integrations = Integrations::fields();
		if (array_key_exists($export->elementType, $integrations))
		{
			$one = $query->one();
			if ($one)
				$this->_fields = $integrations[$export->elementType]($query->one());
		}

		$split = Out::getInstance()->getSettings()['split'];

		if ($query->count() > $split)
			$this->_renderMultiple($export, $query, $split);
		else
			$this->_renderSingle($export, $query);
	}

	/**
	 * @param              $export
	 * @param ElementQuery $query
	 *
	 * @throws \yii\base\ExitException
	 */
	private function _renderSingle ($export, ElementQuery $query)
	{
		$filename = StringHelper::toKebabCase($export->title);

		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename={$filename}.csv");
		header("Pragma: no-cache");

		echo $this->_renderCsv($export, $query);

		\Craft::$app->end();
	}

	private function _renderMultiple ($export, ElementQuery $query, $split)
	{
		$count = $query->count();
		$pages = ceil($count / $split);

		$filename = StringHelper::toKebabCase($export->title);


		$file = @tempnam("tmp", "zip");
		$zip = new ZipArchive();
		$zip->open($file, ZipArchive::CREATE);

		while ($pages--)
		{
			$zip->addFromString(
				$filename . '.' . ($pages + 1) . '.csv',
				$this->_renderCsv($export, clone $query, $split * $pages, $split)
			);
		}

		$zip->close();

		header('Content-Type: application/zip');
		header('Content-Length: ' . filesize($file));
		header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
		readfile($file);
		unlink($file);
	}

	private function _renderCsv ($export, ElementQuery $query, $offset = 0, $limit = null)
	{
		ob_start();

		$out = fopen('php://output', 'w');

		// Output header
		fputcsv($out, $this->_header($export));

		// Output elements
		/** @var Element $item */
		foreach ($query->limit($limit)->offset($offset)->all() as $item)
			fputcsv($out, $this->_row($export, $item));

		// End CSV output
		fclose($out);

		$out = ob_get_clean();
		$out = str_replace("\n", "\r\n", $out);

		return $out;
	}

	private function _header (Export $export)
	{
		$fields        = $this->_fields;
		$fieldSettings = $export->fieldSettings;

		$header = [];

		/** @var Field $field */
		foreach ($fields as $key => $field)
		{
			if (array_key_exists($key, $fieldSettings))
			{
				if (!$fieldSettings[$key]['enabled'])
					continue;

				$heading = $fieldSettings[$key]['heading'];
				$escape = $fieldSettings[$key]['escape'];
			}
			else
			{
				continue;

//				$heading = $field->name;
//				$escape = true;
			}

			if ($escape) {
				$header[] = $heading;
			} else {
				$header = array_merge(
					$header,
					explode(',', $heading)
				);
			}
		}

		return $header;
	}

	private function _row (Export $export, Element $element)
	{
		$fields        = $this->_fields;
		$fieldSettings = $export->fieldSettings;

		$row = [];

		/** @var Field $field */
		foreach ($fields as $key => $field)
		{
			if (array_key_exists($key, $fieldSettings))
			{
				if (!$fieldSettings[$key]['enabled'])
					continue;

				$twig = $fieldSettings[$key]['twig'];
				$escape = $fieldSettings[$key]['escape'];
			}
			else
			{
				continue;

//				$twig = "{{ element.{$field->handle} }}";
//				$escape = true;
			}

			$value = \Craft::$app->view->renderString(
				$twig,
				compact('element')
			);

			if ($escape)
			{
				$row[] = $value;
			}
			else
			{
				$row = array_merge(
					$row,
					explode(',', $value)
				);
			}
		}

		return $row;
	}

}