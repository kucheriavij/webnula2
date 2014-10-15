<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;


/**
 * Class AuthItem
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{authitem}}", primaryKeys={"name"})
 */
class AuthItem extends Entity
{
	/**
	 * @var AuthItem[]
	 */
	public $parents;
	/**
	 * @var AuthItem[]
	 */
	public $childrens;
	/**
	 * @Column(type="string")
	 */
	private $_title;
	/**
	 * @Column(type="string",length=64, notnull=true)
	 */
	private $_name;
	/**
	 * @Column(type="integer", notnull=true)
	 */
	private $_type;
	/**
	 * @Column(type="text")
	 */
	private $_description;
	/**
	 * @Column(type="text")
	 */
	private $_bizrule;
	/**
	 * @Column(type="text")
	 */
	private $_data;

	/**
	 * @return \webnula2\orm\ActiveRecordMetadata
	 */
	public static function metadata()
	{
		return parent::metadata();
	}

	/**
	 *
	 */
	public function afterSchemaUpdate()
	{
		if ( null === $authItem = self::model()->find( "name='Guest'" ) ) {
			$authItem = new AuthItem();
			$authItem->setAttributes( array(
				'title' => \Yii::t( 'webnula2.locale', 'Guest' ),
				'name' => 'Guest',
				'type' => \CAuthItem::TYPE_ROLE,
				'bizrule' => '',
				'data' => '',
				'description' => \Yii::t( 'webnula2.locale', 'Guest group.' )
			) );
			$authItem->save();
		}

		if ( null === $authItem = self::model()->find( "name='Authorized'" ) ) {
			$authItem = new AuthItem();
			$authItem->setAttributes( array(
				'title' => \Yii::t( 'webnula2.locale', 'Authorized' ),
				'name' => 'Authorized',
				'bizrule' => '',
				'type' => \CAuthItem::TYPE_ROLE,
				'data' => '',
				'description' => \Yii::t( 'webnula2.locale', 'Authorized user group.' )
			) );
			$authItem->save();
			\Yii::app()->authManager->addItemChild( "Authorized", 'Guest' );
		}

		if ( null === $authItem = self::model()->find( "name='Administrator'" ) ) {
			$authItem = new AuthItem();
			$authItem->setAttributes( array(
				'title' => \Yii::t( 'webnula2.locale', 'Administrator' ),
				'name' => 'Administrator',
				'bizrule' => '',
				'type' => \CAuthItem::TYPE_ROLE,
				'data' => '',
				'description' => \Yii::t( 'webnula2.locale', 'Administrator user group.' )
			) );
			$authItem->save();

			\Yii::app()->authManager->addItemChild( "Administrator", 'Authorized' );
		}

		if ( null === $authItem = self::model()->find( "name='Root'" ) ) {
			$authItem = new AuthItem();
			$authItem->setAttributes( array(
				'title' => \Yii::t( 'webnula2.locale', 'Root' ),
				'name' => 'Root',
				'bizrule' => '',
				'type' => \CAuthItem::TYPE_ROLE,
				'data' => '',
				'description' => \Yii::t( 'webnula2.locale', 'Root user group.' )
			) );
			$authItem->save();

			\Yii::app()->authManager->addItemChild( "Root", 'Administrator' );
		}
	}

	/**
	 * @param string $className
	 *
	 * @return \CActiveRecord
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{authitem}}';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'title,name', 'required' ),
			array( 'title', 'length', 'max' => 255 ),
			array( 'name', 'length', 'max' => 64 ),
			array( 'type', 'numerical', 'integerOnly' => true ),
			array( 'description,bizrule,data', 'safe' ),
			array( 'parents,childrens', 'safe' ),
		);
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if ( parent::beforeSave() ) {
			$this->description = strip_tags( $this->description );

			return true;
		}

		return false;
	}

	/**
	 * @param $controller
	 *
	 * @return array
	 */
	public function forms( $controller, $type )
	{
		$this->type = $type;

		return array(
			'title' => $this->isNewRecord ? \Yii::t( 'webnula2.locale', 'Create group' ) : \Yii::t( 'webnula2.locale', 'Update group #{name}', array( '{name}' => $this->title ) ),
			'model' => $this,
			'type' => 'form',
			'elements' => array(
				'type' => array(
					'type' => 'hidden'
				),
				'title' => array(
					'type' => 'text',
				),
				'name' => array(
					'type' => 'text',
				),
				'description' => array(
					'type' => 'textarea',
				),
				'bizrule' => array(
					'type' => 'textarea',
					'visible' => \Yii::app()->getUser()->checkAccess( 'Root' ),
				),
				'data' => array(
					'type' => 'textarea',
					'visible' => \Yii::app()->getUser()->checkAccess( 'Root' ),
				),
			),
			'buttons' => array(
				'submit' => array(
					'context' => 'primary',
					'buttonType' => 'submit',
					'label' => \Yii::t( 'webnula2.locale', 'Save' ),
				),
				'cancel' => array(
					'context' => 'default',
					'buttonType' => 'link',
					'url' => array( 'index' ),
					'label' => \Yii::t( 'webnula2.locale', 'Cancel' ),
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'title' => \Yii::t( 'webnula2.locale', 'Title' ),
			'name' => \Yii::t( 'webnula2.locale', 'Name' ),
			'description' => \Yii::t( 'webnula2.locale', 'Description' ),
			'bizrule' => \Yii::t( 'webnula2.locale', 'Bizness rule' ),
			'data' => \Yii::t( 'webnula2.locale', 'Data' )
		);
	}

	/**
	 * @param $controller
	 *
	 * @return array
	 */
	public function buttons( $controller )
	{
		return array(
			array(
				'label' => \Yii::t( 'webnula2.locale', 'Create group' ),
				'context' => 'success',
				'buttonType' => 'link',
				'url' => array( "groups/create" ),
				'icon' => 'icon'
			)
		);
	}

	/**
	 * @return array
	 */
	public function columns()
	{
		return array(
			array(
				'header' => \Yii::t( 'webnula2.locale', 'Title' ),
				'value' => '$data->title'
			),
			array(
				'header' => \Yii::t( 'webnula2.locale', 'Name' ),
				'value' => '$data->name'
			),
			array(
				'class' => 'webnula2\widgets\booster\TbButtonColumn',
				'template' => '{update}{delete}',
				'buttons' => array(
					'update' => array(
						'url' => 'array("update", "name" => $data->name)',
					),
					'delete' => array(
						'url' => 'array("delete", "name" => $data->name)',
					),
				)
			)
		);
	}

	/**
	 * @param int $type
	 *
	 * @return \CActiveDataProvider
	 */
	public function search( $type = \CAuthItem::TYPE_ROLE )
	{
		$criteria = new \CDbCriteria();
		$criteria->compare( 'type', $type, false );

		return parent::provider( $criteria, 20 );
	}
}