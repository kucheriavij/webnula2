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
	 * @var string
	 */
	public $name = null;
	/**
	 * @var bool
	 */
	public $newName = false;
	/**
	 * @var array|Column[]
	 */
	public $addedColumns = array();
	/**
	 * @var array|Column[]
	 */
	public $changedColumns = array();
	/**
	 * @var array|Column[]
	 */
	public $removedColumns = array();
	/**
	 * @var array|Column[]
	 */
	public $renamedColumns = array();

	/**
	 * @var array|Index[]
	 */
	public $addedIndexes = array();
	/**
	 * @var array|Index[]
	 */
	public $changedIndexes = array();
	/**
	 * @var array|Index[]
	 */
	public $removedIndexes = array();

	/**
	 * @var array|ForeignKey[]
	 */
	public $addedForeignKeys = array();
	/**
	 * @var array|ForeignKey[]
	 */
	public $changedForeignKeys = array();
	/**
	 * @var array|ForeignKey[]
	 */
	public $removedForeignKeys = array();


	/**
	 * @var PrimaryKey
	 */
	public $addedPrimaryKey = null;
	/**
	 * @var PrimaryKey
	 */
	public $changedPrimaryKey = null;
	/**
	 * @var PrimaryKey
	 */
	public $removedPrimaryKey = null;

	/**
	 * @param $name
	 */
	public function __construct( $name )
	{
		$this->name = $name;
	}
}