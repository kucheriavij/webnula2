<?php
/**
 * Many to many relation.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;
use webnula2\common\Annotation;
use WideImage\Exception\Exception;


/**
 * Class ManyMany
 * @package webnula2\orm
 */
final class ManyMany extends \CComponent implements Annotation
{
	/**
	 * @var
	 */
	public $name;
	/**
	 * @var
	 */
	public $joinTable;
	/**
	 * @var
	 */
	public $target;
	/**
	 * @var array
	 */
	public $mappedBy = array();
	/**
	 * @var array
	 */
	public $inverseBy = array();

	function validate()
	{
		if( count($this->mappedBy) == 0 ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Property mappedBy cannot be empty.'));
		}
		if( !is_array($this->mappedBy) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Property mappedBy must be array.'));
		}

		if( count($this->inverseBy) == 0 ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Property inverseBy cannot be empty.'));
		}
		if( !is_array($this->inverseBy) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Property inverseBy must be array.'));
		}

		foreach( $this->mappedBy as $mapped ) {
			if( false === $mapped instanceof JoinColumn ) {
				throw new \CException(\Yii::t('webnula2.locale', 'Property mappedBy must contain only JoinColumn instance.'));
			}
		}

		foreach( $this->inverseBy as $mapped ) {
			if( false === $mapped instanceof JoinColumn ) {
				throw new \CException(\Yii::t('webnula2.locale', 'Property inverseBy must contain only JoinColumn instance.'));
			}
		}

		try {
			new \ReflectionClass($this->target);
		} catch( \Exception $e ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Target "{target}" does not exists.',array('{target}' => $this->target)));
		}
	}
} 