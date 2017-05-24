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

}