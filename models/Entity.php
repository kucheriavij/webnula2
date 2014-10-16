<?php
/**
 * Webnula2 entity base.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\models;

use webnula2\orm\ActiveRecordMetadata;


/**
 * Class Entity
 * @package webnula2\models
 */
abstract class Entity extends \CActiveRecord
{
	/**
	 * @Column(type="datetime")
	 */
	protected $_created;
	/**
	 * @Column(type="datetime")
	 */
	protected $_updated;
	/**
	 * @Column(type="integer")
	 * @BelongsTo(target="webnula2\models\User", reference = "id")
	 */
	protected $_created_by;
	/**
	 * @Column(type="integer")
	 * @BelongsTo(target="webnula2\models\User", reference = "id")
	 */
	protected $_updated_by;
	/**
	 * @var array
	 */
	private static $_metadata = array();
	/**
	 * @var array
	 */
	private static $_relations = array();



	/**
	 * @return ActiveRecordMetadata
	 */
	public static function metadata()
	{
		$className = get_called_class();
		if ( !isset( self::$_metadata[$className] ) ) {
			self::$_metadata[$className] = \Yii::app()->schematool->getDriver()->loadMetadataForClass( $className );
		}

		return self::$_metadata[$className];
	}

	/**
	 *
	 */
	public function beforeSchemaUpdate()
	{
		if($this->hasEventHandler('onBeforeSchemaUpdate'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeSchemaUpdate($event);
			return $event->isValid;
		}
		else
			return true;
	}

	/**
	 *
	 */
	public function afterSchemaUpdate()
	{
		if($this->hasEventHandler('onAfterSchemaUpdate'))
			$this->onAfterSchemaUpdate(new CEvent($this));
	}

	/**
	 * This event is raised before the record is saved.
	 * By setting {@link CModelEvent::isValid} to be false, the normal {@link save()} process will be stopped.
	 * @param CModelEvent $event the event parameter
	 */
	public function onBeforeSchemaUpdate($event)
	{
		$this->raiseEvent('onBeforeSchemaUpdate',$event);
	}

	/**
	 * This event is raised after the record is saved.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterSchemaUpdate($event)
	{
		$this->raiseEvent('onAfterSchemaUpdate',$event);
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if( parent::beforeSave() )
		{
			$Yii = \Yii::app();

			if( $Yii instanceof \CWebApplication ) {
				if ( $this->isNewRecord ) {
					$this->created = date( 'Y-m-d H:i:s' );
					$this->created_by = (int)$Yii->getUser()->getId();
				}
				$this->updated = date( 'Y-m-d H:i:s' );
				$this->updated_by = (int)$Yii->getUser()->getId();
			}
			return true;
		}
		return false;
	}

	/**
	 * @param \CDbCriteria $criteria
	 * @param int $count
	 * @param mixed $baseUrl
	 *
	 * @return \CActiveDataProvider
	 */
	public function provider( \CDbCriteria $criteria, $count = 10, $baseUrl = null, $sort = null )
	{
		$config['criteria'] = $criteria;
		if ( $count === false ) {
			$config['pagination'] = false;
		} else if ( is_numeric( $count ) ) {
			$config['pagination'] = array(
				'pageSize' => $count,
				'route' => $baseUrl
			);
		} else {
			// FIXME
		}

		if ( null !== $sort ) {
			$config['sort'] = $sort;
		}

		return new \CActiveDataProvider( $this, $config );
	}

	/**
	 * @param $controller
	 * @param $node
	 * @param $uuid
	 * @param $options
	 */
	public function render($controller, $node, $uuid, $options)
	{
		$options = array_merge($options, array(
			'dataProvider' => $controller->invokeWith( $this, 'search' ),
			'columns' => $controller->invokeWith( $this, 'columns' ),
		));

		$controller->widget( 'webnula2\widgets\booster\TbGridView', $options);
	}
}