<?php
/**
 * Column descriptor.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;
use webnula2\common\Annotation;


/**
 * Class Column
 * @package webnula2\orm
 */
final class Column extends \CComponent implements Annotation
{
	/**
	 * @var
	 */
	private $type;
	/**
	 * @var
	 */
	private $name;
	/**
	 * @var
	 */
	private $length = 0;
	/**
	 * @var int
	 */
	private $precision = 0;
	/**
	 * @var int
	 */
	private $scale = 0;
	/**
	 * @var bool
	 */
	private $notnull = false;
	/**
	 * @var null
	 */
	private $defaultValue = null;
	/**
	 * @var string
	 */
	private $charset = 'utf8_general_ci';
	/**
	 * @var
	 */
	private $comment;

	/**
	 * @var
	 */
	private $table;

	/**
	 * @return string
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * @param string $charset
	 */
	public function setCharset( $charset )
	{
		$this->charset = $charset;
	}

	/**
	 * @return mixed
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * @param mixed $comment
	 */
	public function setComment( $comment )
	{
		$this->comment = $comment;
	}

	/**
	 * @return null
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param null $defaultValue
	 */
	public function setDefaultValue( $defaultValue )
	{
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return mixed
	 */
	public function getLength()
	{
		return $this->length;
	}

	/**
	 * @param mixed $length
	 */
	public function setLength( $length )
	{
		$this->length = $length;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName( $name )
	{
		$this->name = $name;
	}

	/**
	 * @return boolean
	 */
	public function getIsNotnull()
	{
		return $this->notnull;
	}

	/**
	 * @param boolean $notnull
	 */
	public function setNotnull( $notnull )
	{
		$this->notnull = $notnull;
	}

	/**
	 * @return int
	 */
	public function getPrecision()
	{
		return $this->precision;
	}

	/**
	 * @param int $precision
	 */
	public function setPrecision( $precision )
	{
		$this->precision = $precision;
	}

	/**
	 * @return int
	 */
	public function getScale()
	{
		return $this->scale;
	}

	/**
	 * @param int $scale
	 */
	public function setScale( $scale )
	{
		$this->scale = $scale;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType( $type )
	{
		$this->type = $type;
	}

	/**
	 * @return mixed
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param mixed $table
	 */
	public function setTable( $table )
	{
		$this->table = $table;
	}

	/**
	 * @return boolean
	 */
	public function getNotnull()
	{
		return $this->notnull;
	}

	/**
	 * @throws \CException
	 */
	function validate()
	{
		if( empty($this->type) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Type cannot be empty.'));
		}
	}
}