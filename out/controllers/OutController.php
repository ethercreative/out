<?php

namespace Craft;

class OutController extends BaseController
{

	protected $allowAnonymous = ["actionDownload"];

	public function actionIndex ()
	{
		$this->renderTemplate('out/_index');
	}

	public function actionCreateEdit (array $variables = [])
	{

		if (empty($variables['report'])) {
			if (!empty($variables['reportId'])) {
				$variables['report'] =
					craft()->out->getReportById($variables['reportId']);

				if (!$variables['report']) {
					throw new HttpException(404);
				}
			} else {
				$variables['report'] = new Out_ReportModel();
			}
		}

		if (!$variables['report']->id) {
			$variables['title'] = Craft::t('Create a new report');
		} else {
			$variables['title'] = $variables['report']->title;
		}

		$variables['crumbs'] = [
			[
				'label' => Craft::t('Reports'),
				'url'   => UrlHelper::getUrl('out'),
			],
		];

		$variables['continueEditingUrl'] = 'out/{id}';

		// ---

		$channels = [];
		$types    = [];
		$fields   = [
			"-1" => [
				[
					"name" => "Title",
				    "handle" => "title",
				    "type" => "Plain Text",
				],
			    [
			    	"name" => "Post Date",
			        "handle" => "postDate",
			        "type" => "Date/Time"
			    ]
			]
		];

		foreach (
			craft()->sections->getSectionsByType(SectionType::Channel) as
			$section
		) {
			$channels[$section->id] = $section->name;

			$types[$section->id] = [];
			/** @var EntryTypeModel[] $ts */
			$ts = $section->getEntryTypes();
			foreach ($ts as $t) {
				$types[$section->id][$t->id] = $t->name;
				$fields[$t->id]              = [];

				/** @var FieldLayoutFieldModel[] $fs */
				$fs = craft()->fields->getLayoutFieldsById($t->fieldLayoutId);

				foreach ($fs as $f) {
					$field            = $f->getField();
					$fields[$t->id][] = [
						"name"   => $field->name,
						"handle" => $field->handle,
						"type"   => $field->getFieldType()->getName(),
					];
				}
			}
		}

		$variables["channels"] = $channels;
		$variables["types"]    = $types;

		// ---

		$encodedTypes   = JsonHelper::encode($types);
		$encodedFields  = JsonHelper::encode($fields);
		$encodedMapping = JsonHelper::encode($variables["report"]["mapping"]);
		craft()->templates->includeJs(
			"new Out({$encodedTypes}, {$encodedFields}, {$encodedMapping})"
		);
		craft()->templates->includeJsResource("out/out.min.js");
		craft()->templates->includeCssResource("out/out.css");

		$this->renderTemplate('out/_edit', $variables);
	}

	public function actionSave ()
	{
		$this->requirePostRequest();

		$id = craft()->request->getPost('reportId');

		if ($id) {
			$report = craft()->out->getReportById($id);

			if (!$report) {
				throw new Exception(
					Craft::t(
						'No report exists with the ID “{id}”',
						['id' => $id]
					)
				);
			}
		} else {
			$report = new Out_ReportModel();
		}

		$report->mapping   = craft()->request->getPost("mapping");
		$report->channelId = craft()->request->getPost("channel");
		$report->typeId    = craft()->request->getPost("type");
		$report->query     = craft()->request->getPost("query");
		$report->startDate =
			(($startDate = craft()->request->getPost('startDate'))
				? DateTime::createFromString($startDate, craft()->timezone)
				: null
			);
		$report->endDate   =
			(($endDate = craft()->request->getPost('endDate'))
				? DateTime::createFromString($endDate, craft()->timezone)
				: null
			);

		$report->getContent()->title =
			craft()->request->getPost('title', $report->title);

		if (craft()->out->save($report)) {
			craft()->userSession->setNotice(Craft::t('Report saved.'));
			$this->redirectToPostedUrl($report);
		} else {
			craft()->userSession->setError(Craft::t('Couldn’t save report.'));

			craft()->urlManager->setRouteVariables(
				[
					'report' => $report,
				]
			);
		}
	}

	public function actionDelete ()
	{
		$this->requirePostRequest();

		$id = craft()->request->getRequiredPost('reportId');
		if (craft()->elements->deleteElementById($id)) {
			craft()->userSession->setNotice(Craft::t('Report deleted.'));
			$this->redirectToPostedUrl();
		} else {
			craft()->userSession->setError(Craft::t('Couldn’t delete report.'));
		}
	}

	public function actionDownload ()
	{
		$id = craft()->request->getRequiredQuery("id");
		$report = craft()->out->getReportById($id);

		$report->lastDownloaded = new DateTime;
		craft()->out->save($report);

		$filename = StringHelper::toKebabCase($report->title);

		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename={$filename}.csv");
		header("Pragma: no-cache");

		echo craft()->out->download($report);

		craft()->end();
	}

}