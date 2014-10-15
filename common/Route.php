<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 09.10.2014
 * Time: 8:32
 */

namespace webnula2\common;


/**
 * Class Route
 * @package webnula2\common
 */
class Route extends \CComponent implements Annotation {
	private $rules = array();

	/**
	 * @param array $rules
	 */
	public function __construct($rules = array())
	{
		$this->setRules($rules);
	}

	/**
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * @param array $rules
	 */
	public function setRules( $rules )
	{
		$this->rules = $rules;
	}
	/**
	 */
	function validate()
	{
	}
}