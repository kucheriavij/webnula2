<?php
/**
 * Compare two schema and return the instance of the SchemaDiff. Adapted from doctrine project - see authors and license.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;


/**
 * Class Comparator
 * @package webnula2\orm
 */
final class Comparator extends \CComponent
{
	/**
	 * @param Schema $fromSchema
	 * @param Schema $toSchema
	 *
	 * @return SchemaDiff
	 */
	public function compare( Schema $fromSchema, Schema $toSchema )
	{
		$diff = new SchemaDiff();

		$foreignKeysToTable = array();

		foreach ( $toSchema->getTables() AS $table ) {
			if ( !$fromSchema->hasTable( $table ) ) {
				$diff->newTables[$table->name] = $toSchema->getTable( $table->rawName );
			} else {
				$tableDifferences = $this->diffTable( $fromSchema->getTable( $table->rawName ), $table );
				if ( $tableDifferences !== false ) {
					$diff->changedTables[$table->name] = $tableDifferences;
				}
			}
		}

		/* Check if there are tables removed */
		foreach ( $fromSchema->getTables() AS $table ) {
			$table = $fromSchema->getTable( $table->rawName );
			if ( !$toSchema->hasTable( $table ) ) {
				$diff->removedTables[$table->name] = $table;
			}

			// also remember all foreign keys that point to a specific table
			foreach ( $table->getForeignKeys() AS $foreignKey ) {
				if ( !isset( $foreignKeysToTable[$foreignKey->table->name] ) ) {
					$foreignKeysToTable[$foreignKey->table->name] = array();
				}
				$foreignKeysToTable[$foreignKey->table->name][] = $foreignKey;
			}
		}

		foreach ( $diff->removedTables AS $tableName => $table ) {
			if ( isset( $foreignKeysToTable[$tableName] ) ) {
				$diff->orphanedForeignKeys = array_merge( $diff->orphanedForeignKeys, $foreignKeysToTable[$tableName] );
			}
		}

		return $diff;
	}

	/**
	 * @param Table $table1
	 * @param Table $table2
	 *
	 * @return bool|TableDiff
	 */
	public function diffTable( Table $table1, Table $table2 )
	{
		$changes = 0;
		$tableDifferences = new TableDiff( $table1->name );

		$table1Columns = $table1->getColumns();
		$table2Columns = $table2->getColumns();

		/* See if all the fields in table 1 exist in table 2 */
		foreach ( $table2Columns as $columnName => $column ) {
			if ( !$table1->hasColumn( $column ) ) {
				$tableDifferences->addedColumns[$columnName] = $column;
				$changes++;
			}
		}
		/* See if there are any removed fields in table 2 */
		foreach ( $table1Columns as $columnName => $column ) {
			if ( !$table2->hasColumn( $column ) ) {
				$tableDifferences->removedColumns[$columnName] = $column;
				$changes++;
			}
		}

		foreach ( $table1Columns as $columnName => $column ) {
			if ( $table2->hasColumn( $column ) ) {
				$changedProperties = $this->diffColumn( $column, $table2->getColumn( $columnName ) );
				if ( count( $changedProperties ) ) {
					$columnDiff = new ColumnDiff( $column->name, $table2->getColumn( $columnName ), $changedProperties );
					$tableDifferences->changedColumns[$column->name] = $columnDiff;
					$changes++;
				}
			}
		}

		$this->detectColumnRenamings( $tableDifferences );

		$table1Indexes = $table1->getIndexes();
		$table2Indexes = $table2->getIndexes();

		foreach ( $table2Indexes AS $index2Name => $index2Definition ) {
			foreach ( $table1Indexes AS $index1Name => $index1Definition ) {
				if ( $this->diffIndex( $index1Definition, $index2Definition ) === false ) {
					unset( $table1Indexes[$index1Name] );
					unset( $table2Indexes[$index2Name] );
				} else {
					if ( $index1Name == $index2Name ) {
						$tableDifferences->changedIndexes[$index2Name] = $table2Indexes[$index2Name];
						unset( $table1Indexes[$index1Name] );
						unset( $table2Indexes[$index2Name] );
						$changes++;
					}
				}
			}
		}

		foreach ( $table1Indexes AS $index1Name => $index1Definition ) {
			$tableDifferences->removedIndexes[$index1Name] = $index1Definition;
			$changes++;
		}

		foreach ( $table2Indexes AS $index2Name => $index2Definition ) {
			$tableDifferences->addedIndexes[$index2Name] = $index2Definition;
			$changes++;
		}


		// primary key compare
		$pk1 = $table1->getPrimaryKeys();
		$pk2 = $table2->getPrimaryKeys();

		if ( !$pk2->isEmpty && $pk1->isEmpty ) {
			$tableDifferences->addedPrimaryKey = $pk2;
			$changes++;
		} else if ( $pk2->isEmpty && !$pk1->isEmpty ) {
			$tableDifferences->removedPrimaryKey = $pk1;
			$changes++;
		} else if ( $pk2->isEmpty && $pk1->isEmpty && !$pk2->compareTo( $pk1 ) ) {
			$tableDifferences->removedPrimaryKey = $pk1;
			$tableDifferences->changedPrimaryKey = $pk2;
			$changes++;
		}

		$fromFkeys = $table1->getForeignKeys();
		$toFkeys = $table2->getForeignKeys();

		foreach ( $fromFkeys AS $key1 => $constraint1 ) {
			foreach ( $toFkeys AS $key2 => $constraint2 ) {
				if ( $this->diffForeignKey( $constraint1, $constraint2 ) === false ) {
					unset( $fromFkeys[$key1] );
					unset( $toFkeys[$key2] );
				} else {
					if ( strtolower( $constraint1->name ) == strtolower( $constraint2->name ) ) {
						$tableDifferences->changedForeignKeys[] = $constraint2;
						$changes++;
						unset( $fromFkeys[$key1] );
						unset( $toFkeys[$key2] );
					}
				}
			}
		}

		foreach ( $fromFkeys AS $key1 => $constraint1 ) {
			$tableDifferences->removedForeignKeys[] = $constraint1;
			$changes++;
		}

		foreach ( $toFkeys AS $key2 => $constraint2 ) {
			$tableDifferences->addedForeignKeys[] = $constraint2;
			$changes++;
		}

		return $changes ? $tableDifferences : false;
	}

	/**
	 * @param TableDiff $tableDifferences
	 */
	private function detectColumnRenamings( TableDiff $tableDifferences )
	{
		$renameCandidates = array();
		foreach ( $tableDifferences->addedColumns AS $addedColumnName => $addedColumn ) {
			foreach ( $tableDifferences->removedColumns AS $removedColumnName => $removedColumn ) {
				if ( count( $this->diffColumn( $addedColumn, $removedColumn ) ) == 0 ) {
					$renameCandidates[$addedColumn->getName()][] = array( $removedColumn, $addedColumn, $addedColumnName );
				}
			}
		}

		foreach ( $renameCandidates AS $candidate => $candidateColumns ) {
			if ( count( $candidateColumns ) == 1 ) {
				list( $removedColumn, $addedColumn ) = $candidateColumns[0];
				$removedColumnName = strtolower( $removedColumn->getName() );
				$addedColumnName = strtolower( $addedColumn->getName() );

				$tableDifferences->renamedColumns[$removedColumnName] = $addedColumn;
				unset( $tableDifferences->addedColumns[$addedColumnName] );
				unset( $tableDifferences->removedColumns[$removedColumnName] );
			}
		}
	}

	/**
	 * @param ForeignKey $key1
	 * @param ForeignKey $key2
	 *
	 * @return bool
	 */
	public function diffForeignKey( ForeignKey $key1, ForeignKey $key2 )
	{
		if ( array_map( 'strtolower', $key1->getColumns() ) != array_map( 'strtolower',
				$key2->getColumns() )
		) {
			return true;
		}

		if ( array_map( 'strtolower', $key1->getReferences() ) != array_map( 'strtolower', $key2->getReferences() ) ) {
			return true;
		}

		if ( $key1->getOnUpdate() != $key2->getOnUpdate() ) {
			return true;
		}

		if ( $key1->getOnDelete() != $key2->getOnDelete() ) {
			return true;
		}

		return false;
	}

	/**
	 * @param Column $column1
	 * @param Column $column2
	 *
	 * @return array
	 */
	public function diffColumn( Column $column1, Column $column2 )
	{
		$changedProperties = array();
		if ( $column1->type != $column2->type ) {
			$changedProperties[] = 'type';
		}

		if ( $column1->notnull != $column2->notnull ) {
			$changedProperties[] = 'notnull';
		}

		if ( $column1->defaultValue != $column2->defaultValue ) {
			$changedProperties[] = 'default';
		}

		if ( $column1->type === 'string' ) {
			// check if value of length is set at all, default value assumed otherwise.
			$length1 = $column1->length ?: 255;
			$length2 = $column2->length ?: 255;
			if ( $length1 != $length2 ) {
				$changedProperties[] = 'length';
			}
		}

		if ( $column1->type === 'decimal' ) {
			if ( ( $column1->precision ?: 10 ) != ( $column2->precision ?: 10 ) ) {
				$changedProperties[] = 'precision';
			}
			if ( $column1->scale != $column2->scale ) {
				$changedProperties[] = 'scale';
			}
		}

		// only allow to delete comment if its set to '' not to null.
		if ( $column1->comment !== null && $column1->comment != $column2->comment ) {
			$changedProperties[] = 'comment';
		}

		return $changedProperties;
	}

	/**
	 * @param Index $index1
	 * @param Index $index2
	 *
	 * @return bool
	 */
	public function diffIndex( Index $index1, Index $index2 )
	{
		if ( $index1->is( $index2 ) && $index2->is( $index1 ) ) {
			return false;
		}

		return true;
	}
} 