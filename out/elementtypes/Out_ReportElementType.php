<?php

namespace Craft;

class Out_ReportElementType extends BaseElementType
{

	public function getName ()
	{
		return "Reports";
	}

	public function hasContent ()
	{
		return true;
	}

	public function hasTitles ()
	{
		return true;
	}

	public function getSources ($context = null)
	{
		return [
			"*" => ["label" => "All Reports"],
		];
	}

	public function defineAvailableTableAttributes ()
	{
		return [
			"title"          => "Title",
			"channelId"      => "Channel",
			"typeId"         => "Entry Type",
			"startDate"      => "Start Date",
			"endDate"        => "End Date",
			"lastDownloaded" => "Last Downloaded",
		];
	}

	public function defineSortableAttributes ()
	{
		return [
			"startDate"      => "Start Date",
			"endDate"        => "End Date",
			"lastDownloaded" => "Last Downloaded",
		];
	}

	public function getTableAttributeHtml (
		BaseElementModel $element,
		$attribute
	) {
		switch ($attribute) {
			case "startDate":
			case "endDate": {
				/** @var DateTime $date */
				$date = $element->$attribute;

				return $date ? $date->localeDate() : "None";
			}
			case "lastDownloaded": {
				/** @var DateTime $date */
				$date = $element->$attribute;

				return $date ? $date->localeDate() : "Never";
			}
//			case "channel": {
//				$channel = craft()->sections->getSectionById($element->channelId);
//				return $channel->name;
//			}
//			case "type": {
//				$type = craft()->sections->getEntryTypeById($element->typeId);
//				return $type->name;
//			}
			default:
				return parent::getTableAttributeHtml($element, $attribute);
		}
	}

	public function defineCriteriaAttributes ()
	{
		return [
			'startDate'      => AttributeType::Mixed,
			'endDate'        => AttributeType::Mixed,
			'lastDownloaded' => AttributeType::Mixed,
			'order'          => [
				AttributeType::String,
				'default' => 'reports.startDate asc',
			],
		];
	}

	public function modifyElementsQuery (
		DbCommand $query,
		ElementCriteriaModel $criteria
	) {
		$query
			->addSelect(
				"reports.startDate, reports.endDate, reports.lastDownloaded"
			)->join(
				"out_reports reports",
				"reports.id = elements.id"
			);

		if ($criteria->startDate) {
			$query->andWhere(
				DbHelper::parseDateParam(
					'reports.startDate',
					$criteria->startDate,
					$query->params
				)
			);
		}

		if ($criteria->endDate) {
			$query->andWhere(
				DbHelper::parseDateParam(
					'reports.endDate',
					$criteria->endDate,
					$query->params
				)
			);
		}

		if ($criteria->lastDownloaded) {
			$query->andWhere(
				DbHelper::parseDateParam(
					'reports.lastDownloaded',
					$criteria->lastDownloaded,
					$query->params
				)
			);
		}
	}

	public function populateElementModel ($row)
	{
		return Out_ReportModel::populateModel($row);
	}

	public function getEditorHtml (BaseElementModel $element)
	{
		// TODO: Pass all fields here?

		$html = craft()->templates->render(
			'out/_edit',
			[
				'element' => $element,
			]
		);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

}