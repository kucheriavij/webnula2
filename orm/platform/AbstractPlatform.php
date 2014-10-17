<?php
/**
 * Abstract database platform, management mapping entity to database.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */


namespace webnula2\orm\platform;


use webnula2\orm\Column;
use webnula2\orm\ForeignKey;
use webnula2\orm\Index;
use webnula2\orm\PrimaryKey;
use webnula2\orm\Table;
use webnula2\orm\TableDiff;

/**
 * Class AbstractPlatform
 * @package webnula2\orm\platform
 */
abstract class AbstractPlatform extends \CComponent
{
	/**
	 * @var array
	 */
	private static $platformClass = array(
		'mysql' => 'webnula2\orm\platform\MysqlPlatform',
	);
	/**
	 * @var
	 */
	protected $db;
	/**
	 * @var array
	 */
	private $_tableNames = array();
	/**
	 * @var array
	 */
	private $_tables = array();

	/**
	 * @param \CDbConnection $db
	 *
	 * @return AbstractPlatform instance.
	 */
	public static function instantiate( \CDbConnection $db )
	{
		$driverName = $db->getDriverName();
		if ( isset( self::$platformClass[$driverName] ) ) {
			$instance = new self::$platformClass[$driverName]();
			$instance->db = $db;

			return $instance;
		}

		return null;
	}

	/**
	 * @param string $schema
	 *
	 * @return array
	 */
	public function getTables( $schema = '' )
	{
		$tables = array();
		foreach ( $this->getTableNames( $schema ) as $name ) {
			if ( ( $table = $this->getTable( $name ) ) !== null )
				$tables[$name] = $table;
		}

		return $tables;
	}

	/**
	 * @param string $schema
	 *
	 * @return mixed
	 */
	public function getTableNames( $schema = '' )
	{
		if ( !isset( $this->_tableNames[$schema] ) )
			$this->_tableNames[$schema] = $this->findTableNames( $schema );

		return $this->_tableNames[$schema];
	}

	/**
	 * @param string $schema
	 *
	 * @return mixed
	 */
	abstract protected function findTableNames( $schema = '' );

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getTable( $name )
	{
		if ( !isset( $this->_tables[$name] ) ) {
			return $this->_tables[$name] = $this->loadTable( $name );
		}

		return $this->_tables[$name];
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	abstract protected function loadTable( $name );

	/**
	 * @return string
	 */
	public function getCurrentTimestamp()
	{
		return 'CURRENT_TIMESTAMP';
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	abstract function quoteName( $name );

	/**
	 * @param array $field
	 *
	 * @return mixed
	 */
	abstract function extractType( array $field );

	/**
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function addPrimaryKeys( Table $table );

	/**
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function dropPrimaryKeys( Table $table );

	/**
	 * @param PrimaryKey $pk
	 *
	 * @return mixed
	 */
	abstract function addPrimaryKey( PrimaryKey $pk );

	/**
	 * @param PrimaryKey $pk
	 *
	 * @return mixed
	 */
	abstract function dropPrimaryKey( PrimaryKey $pk );

	/**
	 * @param TableDiff $table
	 *
	 * @return mixed
	 */
	abstract function alterTable( TableDiff $table );

	/**
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function dropTable( Table $table );

	/**
	 * @param array $fields
	 *
	 * @return string
	 */
	public function getColumnDeclarationListSQL( Table $table )
	{
		$queryFields = array();
		foreach ( $table->columns as $field ) {
			$query = $this->getColumnDeclarationSQL( $table, $field );
			$queryFields[] = $query;
		}

		return implode( ",\n", $queryFields );
	}

	/**
	 * @param Column $column
	 *
	 * @return string
	 */
	public function getColumnDeclarationSQL( Table $table, Column $column )
	{
		$default = $this->getDefaultValueDeclarationSQL( $column );

		$charset = ( isset( $column->charset ) && $column->charset ) ?
			' ' . $this->getColumnCharsetDeclarationSQL( $column->charset ) : '';

		$collation = ( isset( $column->collation ) && $column->collation ) ?
			' ' . $this->getColumnCollationDeclarationSQL( $column->collation ) : '';

		$notnull = ( isset( $column->notnull ) && $column->notnull ) ? ' NOT NULL' : '';

		$unique = ( isset( $column->unique ) && $column->unique ) ?
			' ' . $this->getUniqueFieldDeclarationSQL() : '';

		$typeDecl = $table->isExtra( $column->name ) ? $this->getPkType() : $this->getSqlType( $column );
		$columnDef = $typeDecl . $charset . $default . $notnull . $unique . $collation;

		if ( isset( $column->comment ) && $column->comment ) {
			$columnDef .= " COMMENT '" . $column['comment'] . "'";
		}

		return $column->name . ' ' . $columnDef;
	}

	/**
	 * @param Column $column
	 *
	 * @return string
	 */
	public function getDefaultValueDeclarationSQL( Column $column )
	{
		$default = !$column->isNotnull ? ' DEFAULT NULL' : '';

		if ( isset( $column->defaultValue ) ) {
			$default = " DEFAULT '" . $column->defaultValue . "'";
			if ( isset( $column->type ) ) {
				if ( in_array( $column->type, array( "integer", 'biginteger' ) ) ) {
					$default = " DEFAULT " . $column->defaultValue;
				} else if ( $column->type === 'datetime' && $column->defaultValue === $this->currentTimestamp ) {
					$default = " DEFAULT " . $this->currentTimestamp;

				} else if ( $column->type === 'boolean' ) {
					$default = " DEFAULT " . ( (bool)$column->defaultValue ? 1 : 0 );
				}
			}
		}

		return $default;
	}

	/**
	 * @return string
	 */
	public function getColumnCharsetDeclarationSQL()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function getColumnCollationDeclarationSQL()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function getUniqueFieldDeclarationSQL()
	{
		return 'UNIQUE';
	}

	/**
	 * @return mixed
	 */
	abstract function getPkType();

	/**
	 * @param Column $column
	 *
	 * @return mixed
	 */
	abstract function getSqlType( Column $column );

	/**
	 * @param Table $table
	 */
	public function createTableSql( Table $table )
	{
		return null;
	}

	/**
	 * @param TableDiff $table
	 */
	protected function preAlterTable( TableDiff $diff )
	{
		$sql = array();

		foreach ( $diff->removedForeignKeys as $fk ) {
			$sql[] = $this->dropForeignKey( $fk, $fk->table );
		}

		foreach ( $diff->changedForeignKeys as $fk ) {
			$sql[] = $this->dropForeignKey( $fk, $fk->table );
		}

		foreach ( $diff->removedIndexes as $index ) {
			$sql[] = $this->dropIndex( $index, $index->table );
		}

		foreach ( $diff->changedIndexes as $index ) {
			$sql[] = $this->dropIndex( $index, $index->table );
		}

		if ( isset( $diff->removedPrimaryKey ) ) {
			$sql[] = $this->dropPrimaryKey( $diff->removedPrimaryKey );
		}

		if ( isset( $diff->changedPrimaryKey ) ) {
			$sql[] = $this->dropPrimaryKey( $diff->changedPrimaryKey );
		}

		return $sql;
	}

	/**
	 * @param ForeignKey $fk
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function dropForeignKey( ForeignKey $fk, Table $table );

	/**
	 * @param Index $index
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function dropIndex( Index $index, Table $table );

	/**
	 * @param TableDiff $diff
	 */
	protected function postAlterTable( TableDiff $diff )
	{
		$sql = array();

		foreach ( $diff->addedIndexes as $index ) {
			$sql[] = $this->addIndex( $index, $index->table );
		}

		foreach ( $diff->changedIndexes as $index ) {
			$sql[] = $this->addIndex( $index, $index->table );
		}

		foreach ( $diff->addedForeignKeys as $fk ) {
			$sql[] = $this->addForeignKey( $fk, $fk->table );
		}

		foreach ( $diff->changedForeignKeys as $fk ) {
			$sql[] = $this->addForeignKey( $fk, $fk->table );
		}

		if ( isset( $diff->addedPrimaryKey ) ) {
			$sql[] = $this->addPrimaryKey( $diff->addedPrimaryKey );
		}

		if ( isset( $diff->changedPrimaryKey ) ) {
			$sql[] = $this->addPrimaryKey( $diff->changedPrimaryKey );
		}

		return $sql;
	}

	/**
	 * @param Index $index
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function addIndex( Index $index, Table $table );

	/**
	 * @param ForeignKey $fk
	 * @param Table $table
	 *
	 * @return mixed
	 */
	abstract function addForeignKey( ForeignKey $fk, Table $table );
} 