<?php
/**
 * Annotation driver - adapted from doctrine project, see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm\driver;

use webnula2\orm\ActiveRecordMetadata;
use webnula2\orm\Table;


/**
 * Class AnnotationDriver
 * @package webnula2\orm\driver
 */
final class AnnotationDriver extends \CComponent
{
	/**
	 * @var array
	 */
	private $metadata = array();
	/**
	 * @var array|ReflectionClass[]
	 */
	private $classes = array();

	/**
	 *
	 */
	function __construct()
	{
	}

	/**
	 * @return array|ReflectionClass[]
	 */
	public function getClasses()
	{
		return $this->classes;
	}

	/**
	 * @param $class
	 *
	 * @return ActiveRecordMetadata
	 * @throws \CException
	 */
	public function loadMetadataForClass( $class )
	{
		if( !($class instanceof \ReflectionClass ) ) {
			$class = new \ReflectionClass($class);
		}

		$ar = new ActiveRecordMetadata();

		$classAnnot = \Yii::app()->annotation->getClassAnnotations( $class );
		if ( !isset( $classAnnot['Entity'] ) ) {
			return null;
		}

		if ( isset( $classAnnot['Table'] ) ) {
			$ar->setTable( $classAnnot['Table'] );
		}

		foreach ( $class->getProperties() as $property ) {
			if( $property->isPrivate() || $property->isProtected() ) {
				$propAnnot = \Yii::app()->annotation->getPropertyAnnotations( $property );

				$propertyName = $property->getName();
				if ( isset( $propAnnot['Column'] ) && $propertyName[0] === '_' ) {
					$column = $propAnnot['Column'];
					$column->name = substr($propertyName, 1);

					if ( isset( $propAnnot['Id'] ) ) {
						$ar->table->setIdentifierType(Table::GENERATOR_AUTO_INCREMENT);
						$ar->table->addPrimaryKey($column->name);
						$column->setNotNull( true );
					}

					$ar->table->addColumn( $column );
				}

				if ( isset( $propAnnot['ManyMany'] ) ) {
					$ar->mapManyToMany( $propAnnot['ManyMany'] );
				}

				if ( isset( $propAnnot['BelongsTo'] ) ) {
					if ( !isset( $propAnnot['Column'] ) ) {
						throw new \CException( "Missing @Column annotation for @BelongsTo" );
					}

					$ar->mapBelongsTo( $propAnnot['Column']->name, $propAnnot['BelongsTo'] );
				}
			}
		}
		return $ar;
	}

	/**
	 * @return array
	 * @throws \CException
	 */
	public function loadMetadataForAll()
	{
		$classes = $this->loadForAll();

		foreach ( $classes as $class ) {
			$className = $class->getName();
			if( !isset($this->metadata[$className])) {
				if ( ( $ar = $this->loadMetadataForClass( $class ) ) !== null ) {
					$this->metadata[$className] = $ar;
				}
			}
		}

		return $this->metadata;
	}

	/**
	 * @return array
	 * @throws \CException
	 */
	public function loadForAll()
	{
		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( \Yii::getPathOfAlias( 'webnula2.models' ), \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach ( $it as $file ) {
			$sourceFile = realpath( $file->getPathName() );
			require_once $sourceFile;
		}

		foreach ( \Yii::app()->getModules() as $id => $config ) {
			if ( !isset( $config['enabled'] ) || $config['enabled'] ) {
				$module = new \ReflectionClass( \Yii::import($config['class'],false) );
				if ( $module->isSubclassOf( 'webnula2\common\WebModule' ) ) {
					$path = dirname( $module->getFileName() );

					$iterator = new \RegexIterator(
						new \RecursiveIteratorIterator(
							new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
							\RecursiveIteratorIterator::LEAVES_ONLY
						),
						'/.+models(\\\|\/).+\.php$/i',
						\RecursiveRegexIterator::GET_MATCH
					);

					foreach ( $iterator as $file ) {
						$sourceFile = realpath( $file[0] );
						require_once $sourceFile;
					}
				}
			}
		}

		$classes = array();
		$declared = get_declared_classes();
		foreach ( $declared as $className ) {
			$class = new \ReflectionClass( $className );
			if( $class->isSubclassOf('webnula2\models\Entity') && !$class->isAbstract() ) {
				$classes[] = $class;
				$this->classes[$class->getName()] = $class;
			}
		}

		return $classes;
	}
} 