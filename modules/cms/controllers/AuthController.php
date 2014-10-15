<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;


use cms\components\Controller;
use cms\models\LoginForm;

class AuthController extends Controller
{
	public function filters()
	{
		return array( 'accessControl' );
	}

	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'login' ),
				'users' => array( '?' )
			),
			array( 'allow',
				'actions' => array( 'logout' ),
				'users' => array( '*' )
			),
			array( 'deny' ),
		);
	}

	public function actionLogin()
	{
		$model = new LoginForm();
		$modelName = \CHtml::modelName( $model );

		if ( isset( $_POST['ajax'] ) && $_POST['ajax'] === 'login-form' ) {
			echo \CActiveForm::validate( $model );
			\Yii::app()->end();
		}

		// collect user input data
		if ( isset( $_POST[$modelName] ) ) {
			$model->attributes = $_POST[$modelName];
			// validate user input and redirect to the previous page if valid
			if ( $model->validate() && $model->login() )
				$this->redirect( \Yii::app()->user->returnUrl );
		}
		// display the login form
		$this->render( 'login', array( 'model' => $model ) );
	}

	public function actionLogout()
	{
		\Yii::app()->getUser()->logout();
		$this->redirect( \Yii::app()->getRequest()->getUrlReferrer() ?: \Yii::app()->getUser()->returnUrl );
	}
} 