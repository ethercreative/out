<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\controllers;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use ether\out\base\Integrations;
use ether\out\elements\Export;
use ether\out\Out;
use ether\out\web\assets\OutAsset;
use ether\out\web\assets\OutIndexAsset;
use Throwable;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;


/**
 * Class OutController
 *
 * @author  Ether Creative
 * @package ether\out\controllers
 * @since   1.0.0
 */
class OutController extends Controller
{

	/**
	 * @return Response
	 * @throws InvalidConfigException
	 */
	public function actionIndex ()
	{
		Craft::$app->view->registerAssetBundle(OutIndexAsset::class);

		return $this->renderTemplate('out/index', [
			'pluginName' => Out::getInstance()->getSettings()->pluginName,
			'exportName' => Out::getInstance()->getSettings()->exportName,
		]);
	}

	/**
	 * @param string|null $exportId
	 *
	 * @return Response
	 * @throws HttpException
	 * @throws InvalidConfigException
	 */
	public function actionEdit ($exportId = null)
	{
		$craft = Craft::$app;

		$variables = [
			'continueEditingUrl' => 'out/{id}',
			'nextExportUrl' => 'out/new',
			'isNewExport' => $exportId === null,
		];

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
			if ($el === Export::class)
				continue;

			/** @var Element $type */
			$type = new $el;

			$sources = [];

			foreach ($type->sources() as $source)
			{
				if (
					!array_key_exists('key', $source)
					|| !array_key_exists('label', $source)
					|| $source['key'] === '*'
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
				'label' => $type->displayName() ?: $el,
				'value' => $el,
			];
		}

		// Fields
		if ($exportId)
		{
			$variables['fields'] = Out::getInstance()->out->fieldsFromElementAndSource(
				$variables['export']->elementType,
				$variables['export']->elementSource
			);
		}
		else
		{
			$variables['fields'] = Out::getInstance()->out->fields();
		}

		// Integrations
		$variables['integrations'] = array_keys(Integrations::fields());

		// Asset
		$craft->view->registerAssetBundle(OutAsset::class);

		return $this->renderTemplate(
			'out/_edit',
			$variables
		);
	}

	/**
	 * @throws Throwable
	 * @throws ElementNotFoundException
	 * @throws Exception
	 * @throws BadRequestHttpException
	 */
	public function actionSave ()
	{
		$request = Craft::$app->request;

		$export = new Export();
		$export->id = $request->getParam('exportId');
		$export->uid = $request->getParam('exportUid');
		$export->title = $request->getRequiredParam('title');
		$export->elementType = $request->getRequiredParam('elementType');
		$export->elementSource = $request->getRequiredParam('elementSource');
		$export->order = $request->getParam('order');
		$export->search = $request->getParam('search');
		$export->limit = $request->getParam('limit');
		$export->startDate = DateTimeHelper::toDateTime($request->getParam('startDate')) ?: null;
		$export->endDate = DateTimeHelper::toDateTime($request->getParam('endDate')) ?: null;
		$export->fieldSettings = $request->getParam('fieldSettings');

		if (Craft::$app->elements->saveElement($export))
			return $this->redirectToPostedUrl($export);

		Craft::$app->getSession()->setError(
			Craft::t('out', 'Couldn’t save export.')
		);

		Craft::$app->getUrlManager()->setRouteParams([
			'export' => $export
		]);

		return null;
	}

	/**
	 * @throws Throwable
	 * @throws BadRequestHttpException
	 */
	public function actionDelete ()
	{
		$exportId = Craft::$app->getRequest()->getRequiredBodyParam('exportId');
		Craft::$app->elements->deleteElementById($exportId);

		return $this->redirect(UrlHelper::cpUrl('out'));
	}

	/**
	 * @param $exportId
	 *
	 * @throws HttpException
	 * @throws ExitException
	 */
	public function actionDl ($exportId)
	{
		/** @var Export $export */
		$export = Export::find()->id($exportId)->one();
		$siteId = Craft::$app->request->getParam(
			'siteId',
			Craft::$app->sites->primarySite->id
		);

		if (!$export) throw new HttpException(404);

		Out::getInstance()->out->generate($export, $siteId);
	}

	/**
	 * @return Response
	 * @throws BadRequestHttpException
	 */
	public function actionFields ()
	{
		$request = Craft::$app->request;
		$element = $request->getRequiredParam('element');
		$source = $request->getRequiredParam('source');

		return $this->asJson(Out::getInstance()->out->fieldsFromElementAndSource(
			$element,
			$source
		));
	}

}