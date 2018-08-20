<?php

namespace ether\out\services;

use craft\base\Component;
use craft\base\Element;
use craft\base\Field;
use ether\out\elements\Export;

class OutService extends Component
{

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

	public function generate (Export $export)
	{
		/** @var Element $element */
		$element = new $export->elementType;

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

		$query = $element::find();

		// TODO: If $query->count() is greater than X, split into multiple files and zip
		\Craft::configure($query, $criteria);

		ob_start();

		$out = fopen('php://output', 'w');

		fputcsv($out, $this->_header($export));

		/** @var Element $item */
		foreach ($query->all() as $item)
			fputcsv($out, $this->_row($export, $item));

		fclose($out);

		$out = ob_get_clean();
		$out = str_replace("\n", "\r\n", $out);

		return $out;
	}

	private function _header (Export $export)
	{
		$fields        = $this->fields();
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
		$fields        = $this->fields();
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