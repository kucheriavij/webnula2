<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\components;


use webnula2\models\Section;
use webnula2\modules\cms\CmsModule;

/**
 * Class Controller
 * @package cms\components
 */
abstract class Controller extends \CController
{
	/**
	 * @var webnula2\models\Entity
	 */
	public $finder;
	/**
	 * @var \CAttributeCollection
	 */
	public $params;
	/**
	 * @var array
	 */
	private $accessRules = array();
	/**
	 * @var string
	 */
	public $homeLink;
	/**
	 *
	 */
	public function init()
	{
		$this->params = \Yii::app()->urlManager->getParams();
		if ( !empty( $this->params['uuid'] ) && $this->id != 'default' ) {
			if ( null === $node = Section::model()->find( 'uuid=?', array( $this->params['uuid'] ) ) ) {
				throw new \CHttpException( 404, CmsModule::t( 'Node "{uuid}" not found.', array( '{uuid}' => $this->params['uuid'] ) ) );
			}
			$this->params->add( 'node', $node );
		}

		$this->homeLink = \CHtml::link(CmsModule::t('Structure'), array('section/index'));
	}

	/**
	 * @return \CAttributeCollection
	 */
	public function getActionParams()
	{
		return $this->params;
	}

	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			'accessControl',
			'postOnly + delete,toggle'
		);
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return $this->accessRules;
	}

	/**
	 * @param $model
	 * @param $method
	 *
	 * @return bool|mixed
	 */
	public function invokeWith( $model, $method )
	{
		$method = new \ReflectionMethod( $model, $method );
		$params = $this->params;
		$ps = array();
		foreach ( $method->getParameters() as $i => $param ) {
			$name = $param->getName();
			if ( isset( $params[$name] ) ) {
				if ( $param->isArray() )
					$ps[] = is_array( $params[$name] ) ? $params[$name] : array( $params[$name] );
				elseif ( !is_array( $params[$name] ) )
					$ps[] = $params[$name];
				else
					return false;
			} elseif ( $param->isDefaultValueAvailable() )
				$ps[] = $param->getDefaultValue();
			else
				return false;
		}

		return $method->invokeArgs( $model, $ps );
	}

	/**
	 * @throws \CHttpException
	 * @throws \Exception
	 */
	public function actionSort()
	{
		$position = $_POST['position'];
		$model = $this->loadModel( $_POST['id'] );

		$tableName = $model->tableName();

		$transaction = \Yii::app()->getDb()->beginTransaction();
		$command = \Yii::app()->getDb()->createCommand();

		if ( $position == -1 ) {
			$next = $this->prev( $model );
		} else if ( $position == 1 ) {
			$next = $this->next( $model );
		}

		try {
			$parent_sort = (int)$command->setText( "SELECT `sort` FROM {$tableName} WHERE `id` = :id LIMIT 1" )->queryScalar( array( 'id' => $next ) );

			$command->setText( "SET @start := 0" )->execute();

			if ( $position == -1 ) {
				$command->setText( "UPDATE {$tableName} SET `sort` = :sort - 5 WHERE `id` = :id LIMIT 1" )->execute( array( 'sort' => $parent_sort, 'id' => $model->id ) );
			} elseif ( $position == 1 ) {
				$command->setText( "UPDATE {$tableName} SET `sort` = :sort + 5 WHERE `id` = :id LIMIT 1" )->execute( array( 'sort' => $parent_sort, 'id' => $model->id ) );
			}

			$command->setText( "UPDATE {$tableName} SET `sort` = (@start := @start+10) WHERE `parent_id` = :parent_id ORDER BY `sort`" )->execute( array( 'parent_id' => $model->parent_id ) );

			$transaction->commit();
		} catch ( \Exception $e ) {
			$transaction->rollback();
			throw $e;
		}
	}

	/**
	 * @param int $id
	 *
	 * @return \CActiveRecord
	 * @throws \CHttpException
	 */
	protected function loadModel( $id )
	{
		$model = $this->finder->findByPk( $id );
		if ( !isset( $model ) ) {
			throw new \CHttpException( CmsModule::t( 'Object #{id} not found.', array( '{id}' => $id ) ) );
		}

		return $model;
	}

	/**
	 * @param \CActiveRecord $model
	 *
	 * @return int
	 */
	private function prev( \CActiveRecord $model )
	{
		$record = \Yii::app()->getDb()
			->createCommand()
			->select( 'id' )
			->from( $model->tableName() )
			->where( 'id != :id AND sort < :sort AND parent_id = :id2', array( 'sort' => $model->sort, 'id2' => $model->parent_id, 'id' => $model->id ) )
			->limit( 1 )
			->order( 'sort DESC' )
			->queryRow();

		return (int)$record['id'];
	}

	/**
	 * @param \CActiveRecord $model
	 *
	 * @return int
	 */
	private function next( \CActiveRecord $model )
	{
		$record = \Yii::app()->getDb()
			->createCommand()
			->select( 'id' )
			->from( $model->tableName() )
			->where( 'id != :id AND sort > :sort AND parent_id = :id2', array( 'sort' => $model->sort, 'id2' => $model->parent_id, 'id' => $model->id ) )
			->order( 'sort ASC' )
			->limit( 1 )
			->queryRow();

		return (int)$record['id'];
	}

	/**
	 * @param int $id
	 * @param string $attribute
	 * @param string $model
	 *
	 * @throws \CHttpException
	 */
	public function actionToggle( $id, $attribute, $model )
	{
		$modelClass = str_replace( '_', '\\', $model );
		$model = \CActiveRecord::model( $modelClass )->findByPk( $id );
		if ( !isset( $model ) ) {
			throw new \CHttpException( CmsModule::t( 'Object #{id} not found.', array( '{id}' => $id ) ) );
		}
		$value = !$model->$attribute;
		$model->updateByPk( $id, array( $attribute => (int)$value ) );
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
	 * @param webnula2\models\Section $node
	 * @param bool $last
	 *
	 * @return array
	 */
	public function breadcrumbs( $node, $last = true )
	{
		$links = array();
		if ( isset( $node ) ) {
			foreach ( $node->parents as $parent ) {
				$links[$parent->title] = array( '/cms/section/index', 'uuid' => $parent->uuid );
			}

			if( $last ) {
				$links[] = $node->title;
			} else {
				$links[$node->title] = array('/cms/section/index', 'uuid' => $node->uuid);
			}
		}

		return $links;
	}

	/**
	 * @param mixed $output
	 */
	public function json( $output )
	{
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );

		echo \CJSON::encode( $output );
		\Yii::app()->end();
	}

	/**
	 * @param array $_rules
	 */
	protected function rules( $_rules = array() )
	{
		$this->accessRules = \CMap::mergeArray( array( array( 'allow',
			'actions' => array( 'toggle', 'delete', 'sort', 'create', 'update' ),
			'roles' => array( 'Administrator' )
		) ), $_rules );
		$this->accessRules[] = array( 'deny' );
	}
}