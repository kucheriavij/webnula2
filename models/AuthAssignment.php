<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;


/**
 * Class AuthAssignment
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{authassignment}}", primaryKeys={"itemname","userid"})
 */
class AuthAssignment extends Entity
{
	/**
	 * @Column(type="string", length=64, notnull=true)
	 * @BelongsTo(target="webnula2\models\AuthItem", reference="name")
	 */
	private $_itemname;

	/**
	 * @Column(type="string", length=64, notnull=true)
	 */
	private $_userid;

	/**
	 * @Column(type="text")
	 */
	private $_bizrule;

	/**
	 * @Column(type="text")
	 */
	private $_data;

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
		return '{{authassignment}}';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'name,userid', 'required' ),
			array( 'name,userid', 'length', 'max' => 64 ),
			array( 'bizrule, data', 'safe' )
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