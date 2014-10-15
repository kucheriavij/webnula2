<?php
/**
 * Represent the change of a column. Adapted from doctrine project - see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


/**
 * Class ColumnDiff
 * @package webnula2\orm
 */
final class ColumnDiff extends \CComponent
{
	/**
	 * @var
	 */
	public $oldColumnName;
	/**
	 * @var Column
	 */
	public $column;
	/**
	 * @var array
	 */
	public $changedProperties = array();

	/**
	 * @param $oldColumnName
	 * @param Column $column
	 * @param array $changedProperties
	 */
	public function __construct( $oldColumnName, Column $column, array $changedProperties = array() )
	{
		$this->oldColumnName = $oldColumnName;
		$this->column = $column;
		$this->changedProperties = $changedProperties;
	}

	/**
	 * @param $propertyName
	 *
	 * @return bool
	 */
	public function hasChanged( $propertyName )
	{
		return in_array( $propertyName, $this->changedProperties );
	}
} 