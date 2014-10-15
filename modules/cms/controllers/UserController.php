<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;

use cms\components\Controller;
use webnula2\models\User;
use webnula2\modules\cms\CmsModule;
use webnula2\widgets\booster\TbForm;

/**
 * Class UserController
 * @package cms\controllers
 */
class UserController extends Controller
{
	/**
	 * @var array|string[]
	 */
	public $models;

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
		$model = new User();

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
		$model = new User( 'register' );
		$model->password = '';

		$form = new TbForm( $model->forms() );
		if ( $form->submitted( 'save' ) && $form->validate() ) {
			$form->model->save();

			if ( empty( $form->model->groups ) ) {
				$form->model->groups = array( 'Authorized' );
			}

			foreach ( $form->model->groups as $groupName ) {
				\Yii::app()->authManager->assign( $groupName, $form->model->id );
			}

			$this->redirect( array( 'update', 'id' => $form->model->id ) );
		}

		$this->render( 'form', array(
			'form' => $form
		) );
	}

	/**
	 * @param $id
	 *
	 * @throws \CHttpException
	 */
	public function actionUpdate( $id )
	{
		$model = $this->loadModel( $id );
		$modelName = \CHtml::modelName( $model );
		$model->setScenario( 'profile' );
		$model->password = '';

		$form = new TbForm( $model->forms( $this ) );
		if ( $form->submitted( 'save' ) ) {
			if ( $form->validate() ) {
				$form->model->save();

				$groups = $form->model->groups;
				if ( empty( $groups ) ) {
					$groups = array( 'Authorized' );
				}

				foreach ( $form->model->getRelated( 'groups', true ) as $group ) {
					\Yii::app()->authManager->revoke( $group->name, $form->model->id );
				}

				foreach ( $groups as $groupName ) {
					\Yii::app()->authManager->assign( $groupName, $form->model->id );
				}

				$this->redirect( array( 'update', 'id' => $form->model->id ) );
			}
		}
		$this->render( 'form', array(
			'form' => $form
		) );
	}

	/**
	 * @param $id
	 *
	 * @return array|\CActiveRecord|mixed|null
	 * @throws \CHttpException
	 */
	protected function loadModel( $id )
	{
		$model = User::model()->findByPk( $id );
		if ( !isset( $model ) ) {
			throw new \CHttpException( CmsModule::t( 'User #{id} not found.', array( '{id}' => $id ) ) );
		}

		return $model;
	}

	/**
	 * @param $id
	 *
	 * @throws \CHttpException
	 */
	public function actionDelete( $id )
	{
		$model = $this->loadModel( $id );
		foreach ( $model->groups as $group ) {
			\Yii::app()->authManager->revoke( $group->name, $id );
		}
		$model->delete();
	}
}