<?php
/**
 * Represent metadata for activerecord.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


/**
 * Class ActiveRecordMetadata
 * @package webnula2\orm
 */
final class ActiveRecordMetadata extends \CComponent
{
	/**
	 * @var Table
	 */
	private $table;
	/**
	 * @var array
	 */
	private $manyToMany = array();
	/**
	 * @var array
	 */
	private $belongsTo = array();

	/**
	 *
	 */
	function __construct()
	{
	}

	/**
	 * @return array
	 */
	public function getBelongsTo()
	{
		return $this->belongsTo;
	}

	/**
	 * @param array $belongsTo
	 */
	public function mapBelongsTo( $name, $belongsTo )
	{
		$this->belongsTo[$name] = $belongsTo;
	}

	/**
	 * @return array
	 */
	public function getManyToMany()
	{
		return $this->manyToMany;
	}

	/**
	 * @param array $manyToMany
	 */
	public function mapManyToMany( $manyToMany )
	{
		$this->manyToMany[] = $manyToMany;
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
}