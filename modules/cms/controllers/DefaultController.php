<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;
use cms\components\Controller;


/**
 * Class DefaultController
 * @package cms\controllers
 */
class DefaultController extends Controller
{
	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			'accessControl'
		);
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'error' ),
				'roles' => array( 'Administrator' ) ),
			array( 'deny' )
		);
	}

	/**
	 *
	 */
	public function actionError()
	{
		if ( $error = \Yii::app()->errorHandler->error ) {
			if ( \Yii::app()->request->isAjaxRequest )
				echo $error['message'];
			else
				$this->render( 'error', $error );
		}
	}
} 