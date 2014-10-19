<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;


use cms\components\Controller;
use webnula2\models\AuthItem;
use webnula2\modules\cms\CmsModule;
use webnula2\widgets\booster\TbForm;

/**
 * Class GroupsController
 * @package cms\controllers
 */
class GroupsController extends Controller
{
	/**
	 * @return array
	 */
	public function filters()
	{
		return array( 'accessControl' );
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'index', 'create', 'update', 'delete' ),
				'roles' => array( 'Administrator' ) ),
			array( 'deny' )
		);
	}

	/**
	 *
	 */
	public function actionIndex()
	{
		$model = new AuthItem();

		$this->render( 'index', array(
			'model' => $model,
			'provider' => $model->search()
		) );
	}

	/**
	 *
	 */
	public function actionCreate()
	{
		$model = new AuthItem();
		$form = new TbForm( $model->forms( $this, \CAuthItem::TYPE_ROLE ) );

		if ( $form->submitted() && $form->validate() ) {
			$form->model->save();

			$this->redirect( array( 'update', 'name' => $form->model->name ) );
		}

		$this->render( 'form', array(
			'form' => $form
		) );
	}

	/**
	 * @param $name
	 *
	 * @throws \CHttpException
	 */
	public function actionUpdate( $name )
	{
		$model = $this->loadModel( $name );
		$form = new TbForm( $model->forms( $this, \CAuthItem::TYPE_ROLE ) );

		if ( $form->submitted() && $form->validate() ) {
			$form->model->save();

			$this->redirect( array( 'update', 'name' => $form->model->name ) );
		}

		$this->render( 'form', array(
			'form' => $form,
			'model' => $model
		) );
	}

	/**
	 * @param int $name
	 *
	 * @return AuthItem|null
	 * @throws \CHttpException
	 */
	protected function loadModel( $name )
	{
		$model = AuthItem::model()->findByPk( $name );
		if ( !isset( $model ) ) {
			throw new \CHttpException( CmsModule::t( 'Group "{name}" not found.', array( '{name}' => $name ) ) );
		}

		return $model;
	}

	/**
	 * @param $name
	 *
	 * @throws \CDbException
	 * @throws \CHttpException
	 */
	public function actionDelete( $name )
	{
		$this->loadModel( $name )->delete();
	}
}