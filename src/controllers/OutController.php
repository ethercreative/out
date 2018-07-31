<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\controllers;

use craft\base\Element;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use ether\out\elements\Export;


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
				'url' => UrlHelper::cpUrl('out')
			]
		];

		// Element Types
		$variables['elementTypeQueries'] = [];
		$variables['elementTypes'] = [];

		foreach ($craft->elements->getAllElementTypes() as $el)
		{
			/** @var Element $type */
			$type = new $el;

			$queryClass = (new \ReflectionClass($el::find()))->name;
			$query      = new $queryClass($el);

			$fields = array_filter(
				array_keys(get_object_vars($query)),
				function ($field) {
					return !in_array($field, OutController::QUERY_IGNORE);
				}
			);

			sort($fields);

			$queryFields = [];
			foreach ($fields as $field) {
				$t = gettype($query->$field);
				$t = $t === 'NULL' ? 'string' : $t;

				if (strpos(strtolower($field), 'date') !== false)
					$t = 'date';

				$label = preg_replace_callback(
					'/([A-Z])/',
					function ($c) {
						return ' ' . $c[1];
					},
					$field
				);
				$label[0] = strtoupper($label[0]);

				$queryFields[] = [
					'handle' => $field,
					'label' => $label,
					'type' => $t,
				];
			}

			$variables['elementTypeQueries'][$el] = $queryFields;

			$variables['elementTypes'][] = [
				'label' => $type->displayName() ?: $el,
				'value' => $el,
			];
		}

		// Fields
		$variables['fields'] = $craft->fields->getAllFields();

		return $this->renderTemplate(
			'out/_edit',
			$variables
		);
	}

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