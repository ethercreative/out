<?php

namespace Craft;

class OutService extends BaseApplicationComponent
{

	public function getReportById ($id)
	{
		return craft()->elements->getElementById($id, 'Out_Report');
	}

	public function save (Out_ReportModel $report)
	{
		$isNew = !$report->id;

		if (!$isNew) {
			$record = Out_ReportRecord::model()->findById($report->id);

			if (!$record) {
				throw new Exception(
					Craft::t(
						'No report exists with the ID “{id}”',
						['id' => $report->id]
					)
				);
			}
		} else {
			$record = new Out_ReportRecord();
		}

		$record->mapping        = $report->mapping;
		$record->channelId      = $report->channelId;
		$record->typeId         = $report->typeId;
		$record->query          = $report->query;
		$record->startDate      = $report->startDate;
		$record->endDate        = $report->endDate;
		$record->lastDownloaded = $report->lastDownloaded;
		$record->sorting        = $report->sorting;
		$record->limit          = $report->limit;

		$record->validate();
		$report->addErrors($record->getErrors());

		if (!$report->hasErrors()) {
			$transaction =
				craft()->db->getCurrentTransaction() === null
					? craft()->db->beginTransaction() : null;

			try {

				if (craft()->elements->saveElement($report)) {
					if ($isNew) {
						$record->id = $report->id;
					}

					$record->save(true);

					if ($transaction !== null) {
						$transaction->commit();
					}

					return true;
				}

			} catch (\Exception $e) {
				if ($transaction !== null) {
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	public function download (Out_ReportModel $report)
	{
		// TODO: Break into chunks of 100?
		$criteria = craft()->elements->getCriteria("Entry");

		if (!empty($report->limit) && is_numeric($report->limit) && $report->limit > 0)
			$criteria->limit = $report->limit;
		else
			$criteria->limit = null;

		$criteria->sectionId = $report->channelId;
		$criteria->type = craft()->sections->getEntryTypeById($report->typeId)->handle;

		if ($report->startDate)
			$criteria->after = $report->startDate;

		if ($report->endDate)
			$criteria->before = $report->endDate;

		if ($report->query)
			$criteria->search = $report->query;

		if ($report->sorting)
			$criteria->order = $report->sorting;

		ob_start();

		$out = fopen('php://output', 'w');

		fputcsv($out, $this->_header($report));

		foreach ($criteria->find() as $entry)
			fputcsv($out, $this->_row($entry, $report));

		fclose($out);

		$out = ob_get_clean();
		$out = str_replace("\n", "\r\n", $out);

		return $out;
	}

	private function _header (Out_ReportModel $report)
	{
		$header = [];

		foreach ($report->mapping as $col)
			if ($col["enabled"])
				$header[] = $col['label'];

		return $header;
	}

	private function _row (EntryModel $entry, Out_ReportModel $report)
	{
		$row = [];

		foreach ($report->mapping as $col)
		{
			if (!$col["enabled"])
				continue;

			if (empty($col["twig"])) {
				$row[] = $this->_field($entry, $col['handle']);
			} else {
				$row[] = craft()->templates->renderString(
					$col["twig"],
					[
						$col['handle'] => $entry->{$col['handle']},
					]
				);
			}
		}

		return $row;
	}

	private function _field (EntryModel $entry, $fieldHandle)
	{
		// Mostly stolen from boboldehampsink/export

		$field = craft()->fields->getFieldByHandle($fieldHandle);
		// TODO: Matrix Support?

		$data = $entry->{$fieldHandle};

		if (is_null($data))
			return "";

		if (is_null($field))
			return $data;

		switch ($field->getFieldType()->getClassHandle())
		{
			case "Assets":
			case "Categories":
			case "Entries":
			case "Users":
			case "Tags":
			{
				$data = $data instanceof ElementCriteriaModel
					? implode(', ', $data->find()) : $data;
				break;
			}
			case "Lightswitch":
			{
				$data = $data == "0" ? "No" : "Yes";
				break;
			}
			case "Table":
			{
				// Parse table checkboxes
				$table = array();
				if (is_array($data)) {
					foreach ($data as $row) {
						// Keep track of column #
						$i = 1;
						// Loop through columns
						foreach ($row as $column => $value) {
							// Get column
							$column = isset($field->settings['columns'][$column])
								? $field->settings['columns'][$column]
								: (isset($field->settings['columns']['col'.$i])
									? $field->settings['columns']['col'.$i]
									: array('type' => 'dummy'));
							// Keep track of column #
							++$i;
							// Parse
							$table[] = $column['type'] == 'checkbox'
								? ($value == 1 ? Craft::t('Yes')
								: Craft::t('No')) : $value;
						}
					}
				}
				// Return parsed data as array
				$data = $table;
				break;
			}
			case "RichText":
			case "Date":
			case "RadioButtons":
			case "Dropdown":
			{
				$data = (string) $data;
				break;
			}
			case "Checkboxes":
			case "MultiSelect":
			{
				// Parse multi select values
				$multi = array();
				foreach ($data as $row) {
					$multi[] = $row->value;
				}
				// Return parsed data as array
				$data = $multi;
				break;
			}
		}

		// If it's an object or an array, make it a string
		if (is_array($data)) {
			$data = StringHelper::arrayToString(ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::flattenArray($data)), ', ');
		}

		// If it's an object, make it a string
		if (is_object($data)) {
			$data = StringHelper::arrayToString(ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::flattenArray(get_object_vars($data))), ', ');
		}

		return $data;
	}

}
