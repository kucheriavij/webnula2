<?php
/**
 * Index descriptor.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;
use webnula2\common\Annotation;


/**
 * Class Index
 * @package webnula2\orm
 */
final class Index extends \CComponent implements Annotation
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
	 * @var bool
	 */
	private $unique = false;

	/**
	 * @var
	 */
	private $table;

	/**
	 * @param $columns
	 * @param $isUnique
	 * @param null $name
	 */
	function __construct( $columns = array(), $isUnique = false, $name = null )
	{
		$this->columns = $columns;
		$this->unique = $isUnique;
		if ( $name === null ) {
			$this->name = sprintf( 'idx_%s', md5( serialize( $this ) ) );
		} else {
			$this->name = $name;
		}
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
	 * @return boolean
	 */
	public function getIsUnique()
	{
		return $this->unique;
	}

	/**
	 * @param boolean $isUnique
	 */
	public function setIsUnique( $isUnique )
	{
		$this->unique = $isUnique;
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
	 * @param array $columnNames
	 *
	 * @return bool
	 */
	public function spansColumns( array $columnNames )
	{
		$sameColumns = true;
		for ( $i = 0; $i < count( $this->columns ); $i++ ) {
			if ( !isset( $columnNames[$i] ) || strtolower( $this->columns[$i] ) != strtolower( $columnNames[$i] ) ) {
				$sameColumns = false;
			}
		}

		return $sameColumns;
	}

	/**
	 * @param Index $other
	 *
	 * @return bool
	 */
	public function is( Index $other )
	{
		if (count($other->columns) != count($this->columns)) {
			return false;
		}

		$sameColumns = $this->spansColumns($other->getColumns());

		if ($sameColumns) {
			if ( !$this->isUnique ) {
				return true;
			} else if ($other->isUnique != $this->isUnique ) {
				return false;
			}
			return true;
		}
		return false;
	}

	function validate()
	{
		if( empty($this->columns) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Columns cannot be empty.'));
		}
		if( !is_array($this->columns) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Columns must be array.'));
		}
	}

	public function overrules(Index $other)
	{
		if (!$this->isUnique && $other->IsUnique) {
			return false;
		}

		if ( $this->spansColumns($other->getColumns()) && $this->isUnique) {
			return true;
		}
		return false;
	}
}