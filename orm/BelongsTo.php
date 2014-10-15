<?php
/**
 * Belongs to descriptor.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;
use webnula2\common\Annotation;


/**
 * Class BelongsTo
 * @package webnula2\orm
 */
final class BelongsTo extends \CComponent implements Annotation
{
	/**
	 * @var
	 */
	public $target;
	/**
	 * @var
	 */
	public $reference;
	/**
	 * @var string
	 */
	public $delete = 'CASCADE';
	/**
	 * @var string
	 */
	public $update = 'CASCADE';

	/**
	 * @throws \CException
	 */
	function validate()
	{
		if( empty($this->reference) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Reference cannot be empty.'));
		}
		if( !in_array(strtoupper($this->delete), array('CASCADE', 'SET NULL', 'RESTRICT'), true)) {
			throw new \CException(\Yii::t('webnula2.locale', 'Delete must contain only one of: "CASCADE", "SET NULL", "RESTRICT".'));
		}
		if( !in_array(strtoupper($this->update), array('CASCADE', 'SET NULL', 'RESTRICT'), true)) {
			throw new \CException(\Yii::t('webnula2.locale', 'Update must contain only one of: "CASCADE", "SET NULL", "RESTRICT".'));
		}
		try {
			new \ReflectionClass($this->target);
		} catch( \Exception $e ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Target "{target}" does not exists.',array('{target}' => $this->target)));
		}
	}
} 