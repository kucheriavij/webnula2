<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;


use cms\components\Controller;
use webnula2\models\Menu;
use webnula2\widgets\booster\TbForm;

/**
 * Class MenuController
 * @package cms\controllers
 */
class MenuController extends Controller {
	/**
	 * @return array
	 */
	public function filters()
	{
		return array('accessControl','postOnly+delete');
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array('allow',
			'actions' => array('index','create','update','delete'),
			'roles' => array('Administrator')),
			array('deny')
		);
	}

	/**
	 *
	 */
	public function actionIndex()
	{
		$model = new Menu();

		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * @param $id
	 *
	 * @throws \CHttpException
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();
	}

	/**
	 * @param $id
	 *
	 * @throws \CHttpException
	 */
	public function actionUpdate($id)
	{
		$model = $this->loadModel($id);
		$form = new TbForm($model->forms());

		if( $form->submitted('save') && $form->validate()) {
			$model->save();
			$this->redirect(array('menu/update', 'id' => $model->id));
		}
		$this->render('form',array('form' => $form));
	}

	/**
	 *
	 */
	public function actionCreate()
	{
		$model = new Menu();
		$form = new TbForm($model->forms());

		if( $form->submitted('save') && $form->validate()) {
			$model->save();
			$this->redirect(array('menu/update', 'id' => $model->id));
		}
		$this->render('form',array('form' => $form));
	}

	/**
	 * @param $id
	 *
	 * @return array|\CActiveRecord|mixed|null
	 * @throws \CHttpException
	 */
	protected function loadModel($id)
	{
		$model = Menu::model()->findByPk($id);
		if( !isset($model) ) {
			throw new \CHttpException(404, \Yii::t('webnula2.locale', 'Menu #{id} not found.', array('{id}' => $id)));
		}
		return $model;
	}
} 