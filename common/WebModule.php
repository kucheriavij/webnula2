<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\common;


/**
 * Class WebModule
 * @package webnula2\common
 */
abstract class WebModule extends \CWebModule {
	/**
	 * @var
	 */
	private $_assetsUrl;
	/**
	 * @var string
	 */
	public $defaultController = 'index';
	/**
	 * @var
	 */
	public $title;

	protected function init()
	{
		$this->_assetsUrl = (YII_DEBUG ?
			\Yii::app()->getAssetManager()->publish($this->getBasePath().'/assets', false, -1, true) :
			\Yii::app()->getAssetManager()->publish($this->getBasePath().'/assets')
		);
	}

	/**
	 * @return mixed
	 */
	public function getAssetsUrl()
	{
		return $this->_assetsUrl;
	}

	/**
	 * @param $message
	 * @param array $params
	 *
	 * @return string
	 */
	public static function t($message, $params = array())
	{
		return \Yii::t(get_called_class().'.locale', $message, $params);
	}

	/**
	 * @param $message
	 * @param array $params
	 *
	 * @return string
	 */
	public function _($message, $params = array())
	{
		return \Yii::t(get_class($this).'.locale', $message, $params);
	}
}