<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;

/**
 * Class Menu
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{menu}}", indexes={
 *  @Index(name="name", columns={"name"})
 * })
 */
class Menu extends Entity {
	/**
	 * @Id
	 * @Column(type="integer")
	 */
	private $_id;
	/**
	 * @Column(type="string")
	 */
	private $_title;
	/**
	 * @Column(type="string")
	 */
	private $_name;
	/**
	 * @Column(type="boolean", defaultValue=1)
	 */
	private $_publish;

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
		return '{{menu}}';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array('title,name','required'),
			array('publish','numerical', 'integerOnly' => true)
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'title' => \Yii::t('webnula2.locale', 'Title'),
			'name' => \Yii::t('webnula2.locale', 'Name'),
			'publish' => \Yii::t('webnula2.locale', 'Publish'),
		);
	}

	/**
	 * @return array
	 */
	public function forms()
	{
		return array(
			'type' => 'form',
			'model' => $this,
			'title' => $this->isNewRecord ? \Yii::t('webnula2.locale', 'Create menu') : \Yii::t('webnula2.locale', 'Update menu #{id}', array('{id}' => $this->id)),
			'elements' => array(
				'title' => array(
					'type' => 'text'
				),

				'name' => array(
					'type' => 'text'
				),

				'publish' => array(
					'type' => 'checkbox'
				)
			),
			'buttons' => array(
				'save' => array(
					'buttonType' => 'submit',
					'label' => \Yii::t('webnula2.locale', 'Save'),
					'context' => 'success',
				)
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
				'header' => '#',
				'value' => '$data->id'
			),
			array(
				'header' => \Yii::t('webnula2.locale', 'Title'),
				'value' => '$data->title'
			),
			array(
				'header' => \Yii::t('webnula2.locale', 'Name'),
				'value' => '$data->name'
			),
			array(
				'class' => 'webnula2\widgets\booster\TbButtonColumn',
				'template' => '{update}{delete}',
				'buttons' => array(
					'update' => array(
						'url' => 'array("menu/update", "id" => $data->id)'
					),
					'delete' => array(
						'url' => 'array("menu/delete", "id" => $data->id)'
					)
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public function buttons()
	{
		return array(
			array(
				'label' => \Yii::t('webnula2.locale','Create menu'),
				'buttonType' => 'link',
				'context' => 'success',
				'url' => array('menu/create'),
			)
		);
	}

	/**
	 * @return \CActiveDataProvider
	 */
	public function search()
	{
		$criteria = new \CDbCriteria();
		return parent::provider($criteria, false);
	}
}