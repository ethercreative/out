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
		    "actions"        => "Actions",
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

				return $date ? $date->localeTime() . " - " . $date->localeDate() : "Never";
			}
			case "channelId": {
				$channel = craft()->sections->getSectionById($element->channelId);
				return $channel->name;
			}
			case "typeId": {
				$type = craft()->sections->getEntryTypeById($element->typeId);
				return $type->name;
			}
			case "actions": {
				$dl = UrlHelper::getActionUrl("out/download", ["id" => $element->id]);
				return "<a href='{$dl}' title='Download'><svg height=\"19px\" version=\"1.1\" viewBox=\"0 0 14 19\" width=\"14px\" xmlns=\"http://www.w3.org/2000/svg\"><g fill=\"none\" fill-rule=\"evenodd\" stroke=\"none\" stroke-width=\"1\"><g fill=\"#0d78f2\" transform=\"translate(-383.000000, -213.000000)\"><g transform=\"translate(383.000000, 213.500000)\"><path d=\"M14,6 L10,6 L10,0 L4,0 L4,6 L0,6 L7,13 L14,6 L14,6 Z M0,15 L0,17 L14,17 L14,15 L0,15 L0,15 Z\" /></g></g></g></svg></a>";
			}
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
			->addSelect("reports.*")->join(
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
		// FIXME: Inline editing support

		$html = craft()->templates->render(
			'out/_edit',
			[
				'report' => $element,
			]
		);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

}