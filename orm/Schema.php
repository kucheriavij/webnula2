<?php
/**
 * Schema.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


/**
 * Class Schema
 * @package webnula2\orm
 */
final class Schema extends \CComponent
{
	/**
	 * @var array
	 */
	private $tables = array();

	/**
	 * @param array $tables
	 */
	function __construct( array $tables )
	{
		foreach ( $tables as $table ) {
			$this->addTable( $table );
		}
	}

	/**
	 * @param Table $table
	 */
	public function addTable( Table $table )
	{
		$this->tables[$table->rawName] = $table;
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function getTable( $name )
	{
		return isset( $this->tables[$name] ) ? $this->tables[$name] : null;
	}

	/**
	 * @return array
	 */
	public function getTables()
	{
		return $this->tables;
	}

	/**
	 * @param Table $table
	 *
	 * @return bool
	 */
	public function hasTable( Table $table )
	{
		return isset( $this->tables[$table->rawName] );
	}
}