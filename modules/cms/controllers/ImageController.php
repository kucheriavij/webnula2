<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace cms\controllers;


use cms\components\Controller;
use webnula2\models\Image;
use webnula2\modules\cms\CmsModule;

/**
 * Class ImageController
 * @package cms\controllers
 */
class ImageController extends Controller
{
	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			'accessControl',
			'postOnly + delete, upload, attribute'
		);
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'delete', 'upload', 'attribute', 'sort' ),
				'roles' => array( 'Administrator' ) ),
			array( 'deny' )
		);
	}

	/**
	 * @param $id
	 *
	 * @throws \CHttpException
	 */
	public function actionDelete( $id )
	{
		$this->loadModel( $id )->delete();
	}

	/**
	 * @param $id
	 *
	 * @return array|\CActiveRecord|mixed|null
	 * @throws \CHttpException
	 */
	protected function loadModel( $id )
	{
		$model = Image::model()->findByPk( $id );
		if ( !isset( $model ) ) {
			throw new \CHttpException( CmsModule::t( 'Image #{id} not found.', array( '{id}' => $id ) ) );
		}

		return $model;
	}

	/**
	 * @param $id
	 * @param $sibling
	 * @param $position
	 *
	 * @throws \CHttpException
	 * @throws \Exception
	 */
	public function actionSort($id, $sibling, $position)
	{
		$model = $this->loadModel( $id );

		$tableName = $model->tableName();

		$transaction = \Yii::app()->getDb()->beginTransaction();
		$command = \Yii::app()->getDb()->createCommand();


		try {
			$siblingSort = (int)$command->setText( "SELECT `sort` FROM {$tableName} WHERE `id` = :id LIMIT 1" )->queryScalar( array( 'id' => $sibling ) );

			$command->setText( "SET @start := 0" )->execute();

			if ( $position == 'before' ) {
				$command->setText( "UPDATE {$tableName} SET `sort` = :sort - 5 WHERE `id` = :id LIMIT 1" )->execute( array( 'sort' => $siblingSort, 'id' => $model->id ) );
			} elseif ( $position == 'after' ) {
				$command->setText( "UPDATE {$tableName} SET `sort` = :sort + 5 WHERE `id` = :id LIMIT 1" )->execute( array( 'sort' => $siblingSort, 'id' => $model->id ) );
			}

			$command->setText( "UPDATE {$tableName} SET `sort` = (@start := @start+10) ORDER BY `sort`" )->execute();

			$transaction->commit();
		} catch ( \Exception $e ) {
			$transaction->rollback();
			throw $e;
		}
	}

	/**
	 * @param $id
	 * @param $attribute
	 * @param $value
	 *
	 * @throws \CHttpException
	 */
	public function actionAttribute( $id, $attribute, $value )
	{
		$model = $this->loadModel( $id );

		$model->setAttribute( $attribute, $value );
		$model->update( array( $attribute ) );
	}

	/**
	 * @throws \CException
	 */
	public function actionUpload()
	{
		if ( $image = Image::createFromUpload( 'fileData' ) ) {
			$this->json(array(
				'title' => $image->title,
				'main' => $image->main,
				'id' => $image->id,
				'url' =>$image->getUrl('t260x180'),
			));
		}
	}
} 