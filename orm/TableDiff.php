<?php
/**
 * Represent the change of a table. Adapted from doctrine project - see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


/**
 * Class TableDiff
 * @package webnula2\orm
 */
final class TableDiff extends \CComponent
{
	/**
	 * @var null
	 */
	public $name = null;
	/**
	 * @var bool
	 */
	public $newName = false;
	/**
	 * @var array
	 */
	public $addedColumns = array();
	/**
	 * @var array
	 */
	public $changedColumns = array();
	/**
	 * @var array
	 */
	public $removedColumns = array();
	/**
	 * @var array
	 */
	public $renamedColumns = array();

	/**
	 * @var array
	 */
	public $addedIndexes = array();
	/**
	 * @var array
	 */
	public $changedIndexes = array();
	/**
	 * @var array
	 */
	public $removedIndexes = array();

	/**
	 * @var array
	 */
	public $addedForeignKeys = array();
	/**
	 * @var array
	 */
	public $changedForeignKeys = array();
	/**
	 * @var array
	 */
	public $removedForeignKeys = array();

	/**
	 * @param $name
	 */
	public function __construct( $name )
	{
		$this->name = $name;
	}
}