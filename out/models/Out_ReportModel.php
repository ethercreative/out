<?php

namespace Craft;

/**
 * Class Out_ReportModel
 *
 * @property {Array} mapping
 * @property {Number} channelId
 * @property {Number} typeId
 * @property {String} query
 * @property {DateTime} startDate
 * @property {DateTime} endDate
 * @property {DateTime} lastDownloaded
 * @property {String} sorting
 * @property {Number} limit
 * @package Craft
 */
class Out_ReportModel extends BaseElementModel
{

	protected $elementType = "Out_Report";

	protected function defineAttributes ()
	{
		return array_merge(
			parent::defineAttributes(),
			[
				"mapping"        => [AttributeType::Mixed, "required" => true],
				"channelId"      => [AttributeType::Number, "required" => true],
				"typeId"         => [AttributeType::Number, "required" => true],
				"query"          => AttributeType::String,
				"startDate"      => AttributeType::DateTime,
				"endDate"        => AttributeType::DateTime,
				"lastDownloaded" => AttributeType::DateTime,
			    "sorting"        => AttributeType::String,
			    "limit"          => AttributeType::Number,
			]
		);
	}

	public function isEditable ()
	{
		return true;
	}

	public function getCpEditUrl ()
	{
		return UrlHelper::getCpUrl('out/' . $this->id);
	}

}