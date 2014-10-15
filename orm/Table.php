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
	 * @var array
	 */
	private $indexes = array();
	/**
	 * @var array
	 */
	private $foreignKeys = array();
	/**
	 * @var array
	 */
	private $primaryKeys = array();

	/**
	 * @var null
	 */
	private $pk = null;

	/**
	 * @param $name
	 */
	function __construct( $name = null )
	{
		$this->setName( $name );
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
		if( $column instanceof Column ) {
			return isset( $this->columns[$column->name] );
		} else if( is_string($column) ) {
			return isset( $this->columns[$column] );
		}
	}

	/**
	 * @param $name
	 *
	 * @return null
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
			$this->addIndex($idx);
		}
	}

	/**
	 * @param Index $index
	 */
	public function addIndex( Index $index )
	{
		foreach ($this->indexes AS $existingIndex) {
			if ($index->is($existingIndex)) {
				return;
			}
		}

		foreach ($this->indexes AS $idxKey => $existingIndex) {
			if ($index->overrules($existingIndex)) {
				unset($this->indexes[$idxKey]);
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
	public function hasIndex( $index ) {
		if( $index instanceof Index ) {
			return isset($this->indexes[$index->name]);
		} else if( is_string($index) ) {
			return isset($this->indexes[$index]);
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
	 * @return array
	 */
	public function getPrimaryKeys()
	{
		return $this->primaryKeys;
	}

	/**
	 * @param $name
	 */
	public function addPrimaryKey( $name )
	{
		$this->primaryKeys[$name] = $name;
	}

	/**
	 * @param $keys
	 */
	public function setPrimaryKeys($keys) {
		foreach( $keys as $key ) {
			$this->primaryKeys[$key]= $key;
		}
	}

	/**
	 * @param $pk
	 */
	public function addPk( $pk )
	{
		$this->pk[$pk->name] = $pk;
	}

	/**
	 * @return bool
	 */
	public function getHasPk()
	{
		return isset( $this->pk );
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function getPk( $name )
	{
		return $this->isPk( $name ) ? $this->pk[$name] : null;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function isPk( $name )
	{
		return isset( $this->pk[$name] );
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
	 * @throws \CException
	 */
	function validate()
	{
		if( empty($this->name) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Name cannot be empty.'));
		}
		if( !is_array($this->indexes) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Indexes must be array.'));
		}
	}
} 