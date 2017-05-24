<?php

namespace Craft;

class Out_ReportRecord extends BaseRecord
{

	public function getTableName ()
	{
		return "out_reports";
	}

	public function defineAttributes ()
	{
		return [
			"mapping"        => [AttributeType::Mixed],
			"channelId"      => [AttributeType::Number],
			"typeId"         => [AttributeType::Number],
			"query"          => [AttributeType::String],
			"startDate"      => [AttributeType::DateTime],
			"endDate"        => [AttributeType::DateTime],
			"lastDownloaded" => [AttributeType::DateTime],
		];
	}

	public function defineRelations ()
	{
		return [
			'element' => [
				static::BELONGS_TO,
				'ElementRecord',
				'id',
				'required' => true,
				'onDelete' => static::CASCADE,
			],
		    'type' => [
		    	static::HAS_ONE,
		        "EntryTypeRecord",
		        "typeId",
		        "required" => true,
		        "onDelete" => static::SET_NULL,
		    ],
		    "channel" => [
		    	static::HAS_ONE,
		        "SectionModel",
		        "channelId",
		        "required" => true,
		        "onDelete" => static::SET_NULL,
		    ]
		];
	}

}