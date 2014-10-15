<?php
/**
 * Descriptor for columns in manymany relation.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\orm;

use webnula2\common\Annotation;


/**
 * Class JoinColumn
 * @package webnula2\orm
 */
final class JoinColumn extends \CComponent implements Annotation
{
	/**
	 * @var
	 */
	public $name;
	/**
	 * @var
	 */
	public $reference;

	/**
	 * @throws \CException
	 */
	function validate()
	{
		if( empty($this->name) || empty($this->reference) ) {
			throw new \CException(\Yii::t('webnula2.locale', 'Name or reference cannot be empty.'));
		}
	}
} 