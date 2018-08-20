<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\controllers;

use craft\base\Element;
use craft\base\Field;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use ether\out\elements\Export;
use ether\out\web\assets\OutAsset;


/**
 * Class OutController
 *
 * @author  Ether Creative
 * @package ether\out\controllers
 * @since   1.0.0
 */
class OutController extends Controller
{

	const QUERY_IGNORE = [
		'withTransforms',
		'elementType',
		'query',
		'subQuery',
		'contentTable',
		'customFields',
		'inReverse',
		'asArray',
		'uid',
		'fixedOrder',
		'leaves',
		'ref',
		'with',
		'level',
		'ancestorDist',
		'descendantDist',
		'select',
		'selectOption',
		'distinct',
		'from',
		'join',
		'having',
		'union',
		'params',
		'queryCacheDuration',
		'queryCacheDependency',
		'where',
		'indexBy',
		'emulateExecution',
		'withStructure',
		'structureId',

		'editable',
		'enabledForSite',
		'siteId',
		'relatedTo',
	];

	/**
	 * @return \yii\web\Response
	 * @throws \yii\base\InvalidConfigException
	 */
	public function actionEdit ()
	{
		$craft = \Craft::$app;

		$variables = [];

		// Title
		$variables['title'] = 'New Export';

		// Breadcrumbs
		$variables['crumbs'] = [
			[
				'label' => 'Out',
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
		$fields = [];

		/** @var Field $field */
		foreach ($craft->fields->getAllFields() as $field)
			$fields[$field->id] = $field->handle;

		$variables['fields'] = $fields;

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
		$fieldLayout = \Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = Export::class;
		\Craft::$app->getFields()->saveLayout($fieldLayout);

		$export = new Export();
		$export->elementType = \Craft::$app->request->getRequiredParam('elementType');
		$export->filter = \Craft::$app->request->getRequiredParam('outFilter')[$export->elementType];
		$export->fieldLayoutId = $fieldLayout->id;

		\Craft::$app->elements->saveElement($export);
	}

}