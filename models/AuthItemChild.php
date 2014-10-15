<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;


/**
 * Class AuthItemChild
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{authitemchild}}")
 */
class AuthItemChild extends Entity
{
	/**
	 * @Column(type="string",length=64)
	 * @BelongsTo(target="webnula2\models\AuthItem", reference="name")
	 */
	private $_parent;

	/**
	 * @Column(type="string",length=64)
	 * @BelongsTo(target="webnula2\models\AuthItem", reference="name")
	 */
	private $_child;

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
	 * @return \webnula2\orm\ActiveRecordMetadata
	 */
	public static function metadata()
	{
		return parent::metadata();
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{authitemchild}}';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'parent,child', 'required' ),
			array( 'parent,child', 'length', 'max' => 64 ),
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array();
	}
}