<?php
/**
 * Foreign keys descriptor.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;
use webnula2\common\Annotation;


/**
 * Class ForeignKey
 * @package webnula2\orm
 */
final class ForeignKey extends \CComponent
{
	/**
	 * @var
	 */
	private $name;
	/**
	 * @var array
	 */
	private $columns = array();
	/**
	 * @var array
	 */
	private $references = array();
	/**
	 * @var
	 */
	private $referenceTable;
	/**
	 * @var string
	 */
	private $on_update = 'CASCADE';
	/**
	 * @var string
	 */
	private $on_delete = 'CASCADE';

	private $table;

	function __construct( Table $table, $columns, $on_delete, $on_update, $references, $refTable, $name = null )
	{
		$this->columns = $columns;
		$this->on_delete = $on_delete;
		$this->on_update = $on_update;
		$this->references = $references;
		$this->referenceTable = $refTable;
		$this->table = $table;

		if ( $name === null ) {
			$names = array_merge(array($table->name, $refTable), $columns, $references);
			$this->name = sprintf( "fk_%s", implode('_', array_map(function($name) {
				return dechex(crc32($name));
			}, $names)) );
		} else
			$this->name = $name;
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @param array $columns
	 */
	public function setColumns( $columns )
	{
		$this->columns = $columns;
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
	 * @return string
	 */
	public function getOnDelete()
	{
		return $this->on_delete;
	}

	/**
	 * @param string $on_delete
	 */
	public function setOnDelete( $on_delete )
	{
		$this->on_delete = $on_delete;
	}

	/**
	 * @return string
	 */
	public function getOnUpdate()
	{
		return $this->on_update;
	}

	/**
	 * @param string $on_update
	 */
	public function setOnUpdate( $on_update )
	{
		$this->on_update = $on_update;
	}

	/**
	 * @return array
	 */
	public function getReferences()
	{
		return $this->references;
	}

	/**
	 * @param array $references
	 */
	public function setReferences( $references )
	{
		$this->references = $references;
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
	 * @return mixed
	 */
	public function getReferenceTable()
	{
		return $this->referenceTable;
	}

	/**
	 * @param mixed $referenceTable
	 */
	public function setReferenceTable( $referenceTable )
	{
		$this->referenceTable = $referenceTable;
	}
}