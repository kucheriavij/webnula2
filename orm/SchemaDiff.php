<?php
/**
 * Represent the change of a schema. Adapted from doctrine project - see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


use webnula2\orm\platform\AbstractPlatform;

/**
 * Class SchemaDiff
 * @package webnula2\orm
 */
final class SchemaDiff extends \CComponent
{
	/**
	 * @var array
	 */
	public $newTables = array();
	/**
	 * @var array
	 */
	public $changedTables = array();
	/**
	 * @var array
	 */
	public $removedTables = array();
	/**
	 * @var array
	 */
	public $orphanedForeignKeys = array();
	/**
	 * @var
	 */
	public $name;

	/**
	 * @param array $newTables
	 * @param array $changedTables
	 * @param array $removedTables
	 */
	public function __construct( $newTables = array(), $changedTables = array(), $removedTables = array() )
	{
		$this->newTables = $newTables;
		$this->changedTables = $changedTables;
		$this->removedTables = $removedTables;
	}

	/**
	 * @param AbstractPlatform $platform
	 *
	 * @return array
	 */
	public function toSql( AbstractPlatform $platform )
	{
		$sql = array();

		foreach ( $this->orphanedForeignKeys AS $orphanedForeignKey ) {
			$sql[] = $platform->dropForeignKey( $orphanedForeignKey, $orphanedForeignKey->table );
		}

		$foreignKeySql = array();
		foreach ( $this->newTables AS $table ) {
			$sql = array_merge(
				$sql,
				$platform->createTableSql( $table )
			);

			foreach ( $table->getForeignKeys() AS $foreignKey ) {
				$foreignKeySql[] = $platform->addForeignKey( $foreignKey, $table );
			}
		}
		$sql = array_merge( $sql, $foreignKeySql );

		foreach ( $this->removedTables AS $table ) {
			$sql[] = $platform->dropTable( $table );
		}

		foreach ( $this->changedTables AS $tableDiff ) {
			$sql = array_merge( $sql, $platform->alterTable( $tableDiff ) );
		}

		return $sql;
	}
} 