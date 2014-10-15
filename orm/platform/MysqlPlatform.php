<?php
/**
 * Manager of the mysql database.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm\platform;


use webnula2\orm\Column;
use webnula2\orm\ForeignKey;
use webnula2\orm\Index;
use webnula2\orm\Table;
use webnula2\orm\TableDiff;

/**
 * Class MysqlPlatform
 * @package webnula2\orm\platform
 */
final class MysqlPlatform extends AbstractPlatform
{
	/**
	 * @var array
	 */
	private $types = array(
		'string' => 'VARCHAR',
		'text' => 'TEXT',
		'longtext' => 'LONGTEXT',
		'integer' => 'INT',
		'float' => 'DOUBLE',
		'decimal' => 'DECIMAL',
		'boolean' => 'TINYINT(1)',
		'date' => 'DATE',
		'datetime' => 'DATETIME',
		'timestamp' => 'TIMESTAMP',
		'time' => 'TIME',
		'binary' => 'LONGBLOB',
	);

	/**
	 * @param Column $column
	 *
	 * @return string
	 */
	function getSqlType( Column $column )
	{
		if ( isset( $this->types[$column->type] ) ) {
			$type = $this->types[$column->type];
			if ( $column->type === 'string' ) {
				return $type . '(' . ( $column->getLength() ?: 255 ) . ')';
			} else if ( $column->type === 'decimal' ) {
				return $type . '(' . $column->getPrecision() . ',' . $column->getScale() . ')';
			}

			return $type;
		} else
			throw new \CException( strtr( 'Type "{name}" not supported for this database.', array( '{name}' => $column->type ) ) );
	}

	/**
	 * @return string
	 */
	function getPkType()
	{
		return 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY';
	}

	/**
	 * @param Table $table
	 *
	 * @return array
	 */
	public function createTableSql( Table $table )
	{
		$columnList = $this->getColumnDeclarationListSQL( $table );
		$name = $table->rawName;

		$query = 'CREATE TABLE ' . $name . "(\n" . $columnList . ") ";
		$query .= 'Engine=InnoDb CHARSET=utf8';

		$sql = array( $query );
		if ( !empty( $table->primaryKeys ) ) {
			$sql[] = $this->addPrimaryKey( $table );
		}

		foreach ( $table->getIndexes() as $index ) {
			$sql[] = $this->addIndex( $index, $table );
		}

		return $sql;
	}

	/**
	 * @param Table $table
	 *
	 * @return string
	 */
	function addPrimaryKey( Table $table )
	{
		$columns = $table->getPrimaryKeys();
		foreach ( $columns as $i => $col )
			$columns[$i] = $this->quoteName( $col );

		return 'ALTER TABLE ' . $table->rawName . ' ADD PRIMARY KEY ('
		. implode( ', ', $columns ) . ' )';
	}

	/**
	 * @param Index $index
	 * @param Table $table
	 *
	 * @return string
	 */
	function addIndex( Index $index, Table $table )
	{
		$cols = array();
		foreach ( $index->getColumns() as $col ) {
			if ( strpos( $col, '(' ) !== false )
				$cols[] = $col;
			else
				$cols[] = $this->quoteName( $col );
		}

		return ( $index->isUnique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ' )
		. $this->quoteName( $index->name ) . ' ON '
		. $table->rawName . ' (' . implode( ', ', $cols ) . ')';
	}

	/**
	 * @param Index $index
	 * @param Table $table
	 *
	 * @return string
	 */
	function dropIndex( Index $index, Table $table )
	{
		return 'DROP INDEX ' . $this->quoteName( $index->name ) . ' ON ' . $table->rawName;
	}

	/**
	 * @param Table $table
	 *
	 * @return string
	 */
	function dropPrimaryKey( Table $table )
	{
		return 'ALTER TABLE ' . $table->rawName . ' DROP PRIMARY KEY';
	}

	/**
	 * @param ForeignKey $fk
	 * @param Table $table
	 *
	 * @return string
	 */
	function addForeignKey( ForeignKey $fk, Table $table )
	{
		$columns = $fk->getColumns();
		foreach ( $columns as $i => $col )
			$columns[$i] = $this->quoteName( $col );
		$refColumns = $fk->getReferences();
		foreach ( $refColumns as $i => $col )
			$refColumns[$i] = $this->quoteName( $col );

		$sql = 'ALTER TABLE ' . $table->rawName
			. ' ADD CONSTRAINT ' . $this->quoteName( $fk->name )
			. ' FOREIGN KEY (' . implode( ', ', $columns ) . ')'
			. ' REFERENCES ' . $this->quoteName( $fk->getReferenceTable() )
			. ' (' . implode( ', ', $refColumns ) . ')';
		if ( $fk->getOnDelete() !== null )
			$sql .= ' ON DELETE ' . $fk->getOnDelete();
		if ( $fk->getOnUpdate() !== null )
			$sql .= ' ON UPDATE ' . $fk->getOnUpdate();

		return $sql;
	}

	/**
	 * @param ForeignKey $fk
	 * @param Table $table
	 *
	 * @return string
	 */
	function dropForeignKey( ForeignKey $fk, Table $table )
	{
		return 'ALTER TABLE ' . $table->rawName
		. ' DROP FOREIGN KEY ' . $this->quoteName( $fk->name );
	}

	/**
	 * @param TableDiff $table
	 *
	 * @return array
	 */
	function alterTable( TableDiff $diff )
	{
		$columnSql = array();
		$queryParts = array();
		if ( $diff->newName !== false ) {
			$queryParts[] = 'RENAME TO ' . $diff->newName;
		}

		foreach ( $diff->addedColumns AS $fieldName => $column ) {
			$queryParts[] = 'ADD COLUMN ' . $this->getColumnDeclarationSQL( $column->table, $column );
		}

		foreach ( $diff->removedColumns AS $column ) {
			$queryParts[] = 'DROP COLUMN ' . $this->quoteName( $column->name );
		}

		foreach ( $diff->changedColumns AS $columnDiff ) {
			$column = $columnDiff->column;
			$queryParts[] = 'CHANGE COLUMN ' . ( $columnDiff->oldColumnName ) . ' '
				. $this->getColumnDeclarationSQL( $column->table, $column );
		}

		foreach ( $diff->renamedColumns AS $oldColumnName => $column ) {
			$queryParts[] = 'CHANGE COLUMN ' . $oldColumnName . ' '
				. $this->getColumnDeclarationSQL( $column->table, $column );
		}

		$sql = array();
		$tableSql = array();

		if ( count( $queryParts ) > 0 ) {
			$sql[] = 'ALTER TABLE ' . $diff->name . ' ' . implode( ", ", $queryParts );
		}
		$sql = array_merge(
			$this->preAlterTable( $diff ),
			$sql,
			$this->postAlterTable( $diff )
		);

		return array_merge( $sql, $tableSql, $columnSql );
	}

	/**
	 * @param Table $table
	 *
	 * @return string
	 */
	function dropTable( Table $table )
	{
		return 'DROP TABLE IF EXISTS ' . $table->rawName;
	}

	/**
	 * @param string $schema
	 *
	 * @return mixed
	 */
	protected function findTableNames( $schema = '' )
	{
		if ( $schema === '' )
			return $this->db->createCommand( 'SHOW TABLES' )->queryColumn();
		$names = $this->db->createCommand( 'SHOW TABLES FROM ' . $this->quoteName( $schema ) )->queryColumn();
		foreach ( $names as &$name )
			$name = $schema . '.' . $name;

		return $names;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	function quoteName( $name )
	{
		return '`' . $name . '`';
	}

	/**
	 * @param $name
	 *
	 * @return null|Table
	 */
	protected function loadTable( $name )
	{
		$table = new Table( $name );

		if ( $this->findColumns( $table ) ) {
			$this->findConstraints( $table );
			$this->findIndexes( $table );

			return $table;
		} else
			return null;
	}

	/**
	 * @param Table $table
	 *
	 * @return bool
	 */
	protected function findColumns( Table $table )
	{
		$sql = 'SHOW FULL COLUMNS FROM ' . $table->rawName;
		try {
			$columns = $this->db->createCommand( $sql )->queryAll();
		} catch ( \Exception $e ) {
			return false;
		}

		foreach ( $columns as $column ) {
			$this->createColumn( $table, $column );
		}

		return true;
	}

	/**
	 * @param Table $table
	 * @param $column
	 */
	protected function createColumn( Table $table, $column )
	{
		$c = new Column();
		$c->setName( $column['Field'] );
		$c->setNotnull( $column['Null'] !== 'YES' );
		if ( strpos( $column['Key'], 'PRI' ) !== false ) {
			$table->addPk( $c );
		}
		$c->setDefaultValue( $column['Default'] );
		$c->setComment( $column['Comment'] );

		$c->setType( $this->extractType( $column ) );

		if ( in_array( $c->type, array( 'string', 'decimal' ) ) ) {
			if ( strpos( $column['Type'], '(' ) && preg_match( '/\((.*)\)/', $column['Type'], $matches ) ) {
				$values = explode( ',', $matches[1] );
				$c->length = $c->precision = (int)$values[0];
				if ( isset( $values[1] ) )
					$c->scale = (int)$values[1];
			}
		}

		$table->addColumn( $c );
	}

	/**
	 * @param array $field
	 *
	 * @return string
	 */
	function extractType( array $field )
	{
		$dbType = strtolower( $field['Type'] );
		if ( false !== $pos = strpos( $field['Type'], '(' ) ) {
			$clearType = strtoupper( substr( $field['Type'], 0, strpos( $field['Type'], '(' ) ) );
		} else {
			$clearType = strtoupper( $field['Type'] );
		}
		$inverse = array_flip( $this->types );
		if ( strpos( $dbType, 'float' ) !== false || strpos( $dbType, 'double' ) !== false )
			return 'float';
		elseif ( strpos( $dbType, 'boolean' ) !== false || strpos( $dbType, 'tinyint(1)' ) !== false )
			return 'boolean';
		elseif ( strpos( $dbType, 'int' ) === 0 && strpos( $dbType, 'unsigned' ) === false || preg_match( '/(bit|tinyint|smallint|mediumint)/', $dbType ) )
			return 'integer';
		elseif ( isset( $inverse[$clearType] ) ) {
			return $inverse[$clearType];
		}
	}

	/**
	 * @param Table $table
	 */
	protected function findConstraints( Table $table )
	{
		$row = $this->db->createCommand( 'SHOW CREATE TABLE ' . $table->rawName )->queryRow();
		$matches = array();

		$regexp = '/CONSTRAINT\s*([^\(^\s]+)\s*FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)(?:\s+ON DELETE (SET NULL|CASCADE|RESTRICT))?(?:\s+ON UPDATE (SET NULL|CASCADE|RESTRICT))?/mi';

		foreach ( $row as $sql ) {
			if ( preg_match_all( $regexp, $sql, $matches, PREG_SET_ORDER ) )
				break;
		}

		foreach ( $matches as $match ) {
			$columns = array_map( 'trim', explode( ',', str_replace( array( '`', '"' ), '', $match[2] ) ) );
			$fks = array_map( 'trim', explode( ',', str_replace( array( '`', '"' ), '', $match[4] ) ) );
			$refTable = str_replace( array( '`', '"' ), '', $match[3] );
			$name = str_replace( array( '`', '"' ), '', $match[1] );

			$onDelete = !empty( $match[5] ) ? $match[5] : 'RESTRICT';
			$onUpdate = !empty( $match[6] ) ? $match[6] : 'RESTRICT';
			$fk = new ForeignKey( $table, $columns, $onDelete, $onUpdate, $fks, $refTable, $name );
			$table->addForeignKey( $fk );
		}
	}

	/**
	 * @param Table $table
	 */
	protected function findIndexes( Table $table )
	{
		$row = $this->db->createCommand( 'SHOW CREATE TABLE ' . $table->rawName )->queryRow();
		$lines = preg_split('/\n/', end($row), -1, PREG_SPLIT_NO_EMPTY);
		foreach( $lines as $line ) {
			$line = trim($line);
			if( preg_match('!^PRIMARY KEY\s*\(([^\)]+)\)!i', $line, $match) ) {
				$columns = array_map( 'trim', explode( ',', str_replace( array( '`', '"' ), '', $match[1] ) ) );
				foreach ( $columns as &$column ) {
					if ( ( $pos = strpos( $column, '(' ) ) !== false ) {
						$column = substr( $column, 0, $pos );
					}
					$table->addPrimaryKey( $column );
				}
			} else if( preg_match('!^(?:UNIQUE )?KEY\s+([^\(^\s]+)\s*\(([^\)]+)\)!i', $line, $match) ) {
				$columns = array_map( 'trim', explode( ',', str_replace( array( '`', '"' ), '', $match[2] ) ) );
				foreach ( $columns as &$column ) {
					if ( ( $pos = strpos( $column, '(' ) ) !== false ) {
						$column = substr( $column, 0, $pos );
					}
				}
				$name = trim( str_replace( array( '`', '"' ), '', $match[1] ) );

				$idx = new Index( $columns, strpos($line, 'UNIQUE') !== false, $name );
				$table->addIndex( $idx );
			}
		}
	}
}