<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


use webnula2\common\Annotation;

/**
 * Class PrimaryKey
 * @package webnula2\orm
 */
class PrimaryKey extends \CComponent implements Annotation {
	/**
	 * @var array
	 */
	private $columns = array();
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var Table
	 */
	private $table;

	/**
	 * @param Table $table
	 * @param array $columns
	 * @param string $name
	 */
	public function __construct(Table $table, $name = null) {
		$this->table = $table;
	}

	/**
	 * @return bool
	 */
	public function getIsEmpty()
	{
		return count($this->columns) == 0;
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		$this->validateKey();
		return $this->columns;
	}

	/**
	 * @param $columns
	 *
	 * @throws \CException
	 */
	public function setColumns( $columns )
	{
		foreach( $columns as $column ) {
			$this->columns[$column] = $column;
		}

		$this->name = sprintf('pk_%s_%s', crc32($this->table->rawName), crc32(serialize($this->columns)));
	}

	/**
	 * @param $name
	 *
	 * @throws \CException
	 */
	public function addColumn($name) {
		$this->columns[$name] = $name;
		$this->name = sprintf('pk_%s_%s', crc32($this->table->rawName), crc32(serialize($this->columns)));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName( $name )
	{
		$this->name = $name;
	}

	/**
	 * @return Table
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param $table
	 *
	 * @throws \CException
	 */
	public function setTable( $table )
	{
		$this->table = $table;
	}

	/**
	 * @throws \CException
	 */
	public function validateKey()
	{
		foreach( $this->columns as $column ) {
			if( !$this->table->hasColumn($column) ) {
				throw new \CException(\Yii::t('webnula2.locale', 'Table "{table}" does not have column "{column}".', array('{table}' => $this->table->rawName, '{column}' =>$column)));
			}
		}
	}

	/**
	 * @param PrimaryKey $other
	 *
	 * @return bool
	 */
	public function compareTo(PrimaryKey $other) {
		if( count($this->columns) != count($other->columns) ) {
			return false;
		}
		return md5(serialize( $this->columns )) === md5(serialize( $other->columns ));
	}

	/**
	 */
	function validate()
	{
		// STUB: see addTable method
	}
}