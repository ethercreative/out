<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\controllers;

use craft\base\Element;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use ether\out\elements\Export;
use ether\out\Out;
use ether\out\web\assets\OutAsset;
use yii\web\HttpException;


/**
 * Class OutController
 *
 * @author  Ether Creative
 * @package ether\out\controllers
 * @since   1.0.0
 */
class OutController extends Controller
{

	public function actionIndex ()
	{
		return $this->renderTemplate('out/index', [
			'pluginName' => Out::getInstance()->getSettings()->pluginName,
			'exportName' => Out::getInstance()->getSettings()->exportName,
		]);
	}

	/**
	 * @param string|null $exportId
	 *
	 * @return \yii\web\Response
	 * @throws HttpException
	 * @throws \yii\base\InvalidConfigException
	 */
	public function actionEdit ($exportId = null)
	{
		$craft = \Craft::$app;

		$variables = [];

		// Export
		if ($exportId)
		{
			/** @var Export $export */
			$export = Export::find()->id($exportId)->one();
			if (!$export) throw new HttpException(404);
			$variables['export'] = $export;
		} else {
			$variables['export'] = new Export();
		}

		// Breadcrumbs
		$variables['crumbs'] = [
			[
				'label' => Out::getInstance()->getSettings()->pluginName,
				'url' => UrlHelper::cpUrl('out'),
			],
		];

		// Element Types
		$variables['elementSources'] = [];
		$variables['elementTypes'] = [];

		foreach ($craft->elements->getAllElementTypes() as $el)
		{
			/** @var Element $type */
			$type = new $el;

			$sources = [];

			foreach ($type->sources() as $source)
			{
				if (
					!array_key_exists('key', $source)
					|| !array_key_exists('label', $source)
				) continue;

				$sources[] = [
					'label' => $source['label'],
					'value' => $source['key'],
				];
			}

			if (empty($sources))
				continue;

			$variables['elementSources'][$el] = $sources;

			$variables['elementTypes'][] = [
				'label'   => $type->displayName() ?: $el,
				'value'   => $el,
			];
		}

		// Fields
		$variables['fields'] = Out::getInstance()->out->fields();

		// Asset
		$craft->view->registerAssetBundle(OutAsset::class);

		return $this->renderTemplate(
			'out/_edit',
			$variables
		);
	}

	/**
	 * @throws \Throwable
	 * @throws \craft\errors\ElementNotFoundException
	 * @throws \yii\base\Exception
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function actionSave ()
	{
		$request = \Craft::$app->request;

		$export = new Export();
		$export->id = $request->getParam('exportId');
		$export->title = $request->getRequiredParam('title');
		$export->elementType = $request->getRequiredParam('elementType');
		$export->elementSource = $request->getRequiredParam('elementSource');
		$export->order = $request->getParam('order');
		$export->search = $request->getParam('search');
		$export->limit = $request->getParam('limit');
		$export->startDate = DateTimeHelper::toDateTime($request->getParam('startDate')) ?: null;
		$export->endDate = DateTimeHelper::toDateTime($request->getParam('endDate')) ?: null;
		$export->fieldSettings = $request->getParam('fieldSettings');

		if (!\Craft::$app->elements->saveElement($export))
		{
			\Craft::$app->getSession()->setError(
				\Craft::t('out', 'Couldn’t save export.')
			);


			\Craft::$app->getUrlManager()->setRouteParams([
				'export' => $export
			]);

			return null;
		}

		$this->redirect($export->getCpEditUrl());
	}

	/**
	 * @throws \Throwable
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function actionDelete ()
	{
		$exportId = \Craft::$app->getRequest()->getRequiredBodyParam('exportId');
		\Craft::$app->elements->deleteElementById($exportId);

		return $this->redirect(UrlHelper::cpUrl('out'));
	}

	/**
	 * @param $exportId
	 *
	 * @throws HttpException
	 * @throws \yii\base\ExitException
	 */
	public function actionDl ($exportId)
	{
		/** @var Export $export */
		$export = Export::find()->id($exportId)->one();
		if (!$export) throw new HttpException(404);

		$filename = StringHelper::toKebabCase($export->title);

		$csv = Out::getInstance()->out->generate($export);

		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename={$filename}.csv");
		header("Pragma: no-cache");

		echo $csv;

		\Craft::$app->end();
	}

}