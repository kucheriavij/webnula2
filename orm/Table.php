<?php
/**
 * Table descriptor.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;

use webnula2\common\Annotation;


/**
 * Class Table
 * @package webnula2\orm
 */
final class Table extends \CComponent implements Annotation
{
	/**
	 * @var
	 */
	private $name;
	/**
	 * @var
	 */
	private $rawName;
	/**
	 * @var
	 */
	private $schemaName;
	/**
	 * @var array
	 */
	private $columns = array();
	/**
	 * @var array|Index[]
	 */
	private $indexes = array();
	/**
	 * @var array|ForeignKey[]
	 */
	private $foreignKeys = array();
	/**
	 * @var PrimaryKey
	 */
	private $primaryKey = null;

	/**
	 * @var int
	 */
	private $identifierType;

	/**
	 * @var string
	 */
	private $engine = null;

	/**
	 * @var string
	 */
	private $charset = null;

	/**
	 *
	 */
	const GENERATOR_NONE = 0;

	/**
	 *
	 */
	const GENERATOR_AUTO_INCREMENT = 1;

	/**
	 * @param $name
	 */
	function __construct( $name = null )
	{
		$this->setName( $name );

		$this->setIdentifierType(self::GENERATOR_NONE);

		$this->primaryKey = new PrimaryKey( $this );
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @param mixed $column
	 *
	 * @return bool
	 */
	public function hasColumn( $column )
	{
		if ( $column instanceof Column ) {
			return isset( $this->columns[$column->name] );
		} else if ( is_string( $column ) ) {
			return isset( $this->columns[$column] );
		}
	}

	/**
	 * @param $name
	 *
	 * @return null|Column
	 */
	public function getColumn( $name )
	{
		return isset( $this->columns[$name] ) ? $this->columns[$name] : null;
	}

	/**
	 * @param Column $column
	 */
	public function addColumn( Column $column )
	{
		$this->columns[$column->name] = $column;
		$column->setTable( $this );
	}

	/**
	 * @return array
	 */
	public function getForeignKeys()
	{
		return $this->foreignKeys;
	}

	/**
	 * @param ForeignKey $fk
	 */
	public function addForeignKey( ForeignKey $fk )
	{
		$this->foreignKeys[$fk->name] = $fk;
		$fk->setTable( $this );
	}

	/**
	 * @return array
	 */
	public function getIndexes()
	{
		return $this->indexes;
	}

	/**
	 * @param array $indexes
	 */
	public function setIndexes( array $indexes )
	{
		foreach ( $indexes as $idx ) {
			$this->addIndex( $idx );
		}
	}

	/**
	 * @param Index $index
	 */
	public function addIndex( Index $index )
	{
		foreach ( $this->indexes AS $existingIndex ) {
			if ( $index->is( $existingIndex ) ) {
				return;
			}
		}

		foreach ( $this->indexes AS $idxKey => $existingIndex ) {
			if ( $index->overrules( $existingIndex ) ) {
				unset( $this->indexes[$idxKey] );
			}
		}

		$this->indexes[$index->name] = $index;
		$index->setTable( $this );
	}

	/**
	 * @param $index
	 *
	 * @return bool
	 */
	public function hasIndex( $index )
	{
		if ( $index instanceof Index ) {
			return isset( $this->indexes[$index->name] );
		} else if ( is_string( $index ) ) {
			return isset( $this->indexes[$index] );
		}
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
		$conn = \Yii::app()->getDb();
		if ( $conn->tablePrefix !== null && $name != '' )
			$name = preg_replace( '/{{(.*?)}}/', $conn->tablePrefix . '\1', $name );

		$parts = explode( '.', str_replace( array( '`', '"' ), '', $name ) );
		if ( isset( $parts[1] ) ) {
			$this->schemaName = $parts[0];
			$this->name = $parts[1];
			$this->rawName = \Yii::app()->schematool->quoteName( $this->schemaName ) . '.' . \Yii::app()->schematool->quoteName
				( $this->name );
		} else {
			$this->name = $parts[0];
			$this->rawName = \Yii::app()->schematool->quoteName( $this->name );
		}
	}

	/**
	 * @return bool
	 */
	public function getIsPrimaryKeyComposite()
	{
		return count( $this->primaryKey->columns ) > 1;
	}

	/**
	 * @param int $type
	 */
	public function setIdentifierType( $type )
	{
		$this->identifierType = $type;
	}

	/**
	 * @return PrimaryKey
	 */
	public function getPrimaryKeys()
	{
		return $this->primaryKey;
	}

	/**
	 * @param string $name
	 */
	public function addPrimaryKey( $name )
	{
		if ( $this->identifierType === self::GENERATOR_AUTO_INCREMENT && $this->getIsPrimaryKeyComposite() ) {
			throw new \CException( \Yii::t( 'webnula2.locale', 'Table "{name}" must have only one primary key with AUTO_INCREMENT.' ) );
		}
		if( ($column = $this->getColumn($name)) ) {
			$column->setNotnull(true);
		}
		$this->primaryKey->addColumn( $name );
	}

	/**
	 * @param array $keys
	 */
	public function setPrimaryKeys( array $keys )
	{
		foreach( $keys as $name )  {
			if( ($column = $this->getColumn($name)) ) {
				$column->setNotnull(true);
			}
		}
		$this->primaryKey->setColumns( $keys );

		if ( $this->identifierType === self::GENERATOR_AUTO_INCREMENT && $this->getIsPrimaryKeyComposite() ) {
			throw new \CException( \Yii::t( 'webnula2.locale', 'Table "{name}" must have only one primary key with AUTO_INCREMENT.' ) );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasPrimaryKey( $key )
	{
		return isset( $this->primaryKey ) && isset( $this->primaryKey->columns[$key] );
	}

	/**
	 * @return mixed
	 */
	public function getRawName()
	{
		return $this->rawName;
	}

	/**
	 * @return mixed
	 */
	public function getSchemaName()
	{
		return $this->schemaName;
	}

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
	 * @return string
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	/**
	 * @param string $engine
	 */
	public function setEngine( $engine )
	{
		$this->engine = $engine;
	}


	/**
	 * @throws \CException
	 */
	function validate()
	{
		if ( empty( $this->name ) ) {
			throw new \CException( \Yii::t( 'webnula2.locale', 'Name cannot be empty.' ) );
		}
		if ( !is_array( $this->indexes ) ) {
			throw new \CException( \Yii::t( 'webnula2.locale', 'Indexes must be array.' ) );
		}
	}
} 