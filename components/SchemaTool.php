<?php
/**
 * Schema tools - application component to manipulate dataset schema and models.
 * Adapted from doctrine project, see authors and license.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\components;


use webnula2\orm\ActiveRecordMetadata;
use webnula2\orm\BelongsTo;
use webnula2\orm\Column;
use webnula2\orm\Comparator;
use webnula2\orm\driver\AnnotationDriver;
use webnula2\orm\ForeignKey;
use webnula2\orm\Index;
use webnula2\orm\platform\AbstractPlatform;
use webnula2\orm\Schema;
use webnula2\orm\SchemaDiff;
use webnula2\orm\Table;

/**
 * Class SchemaTool
 * @package webnula2\components
 */
final class SchemaTool extends \CApplicationComponent
{
	/**
	 * @var webnula2\orm\platform\AbstractPlatform
	 */
	private $platform;
	/**
	 * @var webnula2\orm\driver\AnnotationDriver
	 */
	private $driver;

	/**
	 *
	 */
	public function init()
	{
		parent::init();
		$db = \Yii::app()->getDb();

		$this->driver = new AnnotationDriver();
		$this->platform = AbstractPlatform::instantiate( $db );
	}

	/**
	 * @return mixed
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * @return mixed
	 */
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function quoteName( $name )
	{
		return $this->platform->quoteName( $name );
	}

	/**
	 *
	 */
	public function updateCommand()
	{
		$toSchema = $this->getSchemaFromMetadata();
		$fromSchema = new Schema( $this->platform->getTables() );

		$comparator = new Comparator();
		$diff = $comparator->compare( $fromSchema, $toSchema );

		$this->beforeUpdate();

		$this->execSQL( $diff );

		$this->afterUpdate();
	}

	/**
	 * @return Schema
	 */
	private function getSchemaFromMetadata()
	{
		$listAr = $this->driver->loadMetadataForAll();
		$schema = new Schema( array() );

		foreach ( $listAr as $ar ) {
			$table = $ar->getTable();

			$this->_gatherRealtions( $table, $ar, $schema );

			$schema->addTable( $table );
		}

		return $schema;
	}

	/**
	 * @param Table $table
	 * @param ActiveRecordMetadata $ar
	 * @param Schema $schema
	 *
	 * @throws \CException
	 */
	private function _gatherRealtions( Table $table, ActiveRecordMetadata $ar, Schema $schema )
	{
		foreach ( $ar->getManyToMany() as $manyToMany ) {
			$joinTable = new Table( $manyToMany->joinTable );
			if ( $schema->hasTable( $joinTable ) ) {
				throw new \CException( strtr('Target "{name}" already exists.', array( '{name}' => $joinTable->rawName ) ) );
			}
			$schema->addTable( $joinTable );

			$this->_gatherRelationJoinColumns( $manyToMany->mappedBy, $joinTable, $table );

			$target = $this->driver->loadMetadataForClass( $manyToMany->target );
			$this->_gatherRelationJoinColumns( $manyToMany->inverseBy, $joinTable, $target->table );
		}

		foreach ( $ar->getBelongsTo() as $columnName => $belongsTo ) {
			$this->_gatherRelation( $columnName, $belongsTo, $table );
		}
	}

	/**
	 * @param $joinColumns
	 * @param Table $joinTable
	 * @param Table $source
	 *
	 * @throws \CException
	 */
	private function _gatherRelationJoinColumns( $joinColumns, Table $joinTable, Table $source )
	{
		foreach ( $joinColumns as $joinColumn ) {
			$fieldName = $joinColumn->name;
			$referenceName = $joinColumn->reference;

			if ( !$joinTable->hasColumn( $fieldName ) ) {
				$referenceColumn = $source->getColumn( $referenceName );
				if ( !isset( $referenceColumn ) ) {
					throw new \CException( strtr('Column "{name}" not found in "{table}".',
						array( '{name}' => $referenceName, '{table}' => $source->getName() ) ) );
				}
				$column = new Column();
				$column->setName( $fieldName );
				$column->setType( $referenceColumn->type );
				if ( $column->type === 'string' ) {
					$column->setLength( $referenceColumn->length ?: 255 );
				} else if ( $column->type === 'decimal' ) {
					$column->setPrecision( $referenceColumn->getPrecision() );
					$column->setScale( $referenceColumn->getScale() );
				}
				$column->setDefaultValue( $this->defaultByType( $column ) );
				$joinTable->addColumn( $column );
			}

			$fk = new ForeignKey( $joinTable, array( $fieldName ), 'CASCADE', 'CASCADE', array( $referenceName ),
				$source->name );
			$joinTable->addIndex( new Index( array( $fieldName ), false, $fk->name ) );
			$joinTable->addForeignKey( $fk );
			$joinTable->addPrimaryKey( $fieldName );
		}
	}

	/**
	 * @param Column $column
	 *
	 * @return int|null
	 */
	private function defaultByType( Column $column )
	{
		$column->setNotnull( true );
		if ( $column->type === 'integer' ) {
			return 0;
		} else {
			return null;
		}
	}

	/**
	 * @param $columnName
	 * @param BelongsTo $relation
	 * @param Table $table
	 */
	private function _gatherRelation( $columnName, BelongsTo $relation, Table $table )
	{
		$target = $this->driver->loadMetadataForClass( $relation->target );
		$fk = new ForeignKey( $table, array( $columnName ), $relation->delete, $relation->update,
			array( $relation->reference ), $target->table->name );
		$table->addForeignKey( $fk );
		$table->addIndex( new Index( array( $columnName ), false, $fk->name ) );
	}

	/**
	 * @param SchemaDiff $diff
	 *
	 * @throws \Exception
	 */
	private function execSQL( SchemaDiff $diff )
	{
		$db = \Yii::app()->getDb();
		$command = $db->createCommand();
		$transaction = $db->beginTransaction();

		try {
			foreach ( $diff->toSql( $this->platform ) as $sql ) {
				$command->setText( $sql )->execute();
			}
			$transaction->commit();
		} catch ( \Exception $e ) {
			$transaction->rollback();
			throw $e;
		}
	}

	private function beforeUpdate()
	{
		$db = \Yii::app()->getDb();

		$transaction = $db->beginTransaction();
		try {
			foreach( $this->driver->getClasses() as $class ) {
				$instance = $class->newInstance();
				$instance->beforeSchemaUpdate();
			}
			$transaction->commit();
		} catch( \Exception $e ) {
			$transaction->rollback();
			throw $e;
		}
	}

	private function afterUpdate()
	{
		$db = \Yii::app()->getDb();

		$transaction = $db->beginTransaction();
		try {
			foreach( $this->driver->getClasses() as $class ) {
				$instance = $class->newInstance();
				$instance->afterSchemaUpdate();
			}
			$transaction->commit();
		} catch( \Exception $e ) {
			$transaction->rollback();
			throw $e;
		}
	}
} 