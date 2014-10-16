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
	 * @var string
	 */
	private $_assetsUrl;

	/**
	 * @var string
	 */
	public $defaultController = 'index';

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var \CClientScript
	 */
	public $cs;

	protected function init()
	{
		$this->cs = \Yii::app()->getClientScript();
		if( is_dir($this->getBasePath().'/assets') ) {
			$this->_assetsUrl = ( YII_DEBUG ?
				\Yii::app()->getAssetManager()->publish( $this->getBasePath() . '/assets', false, -1, true ) :
				\Yii::app()->getAssetManager()->publish( $this->getBasePath() . '/assets' )
			);
		}
	}

	/**
	 * @return mixed
	 */
	public function getAssetsUrl()
	{
		return $this->_assetsUrl;
	}

	/**
	 * @param string $cssFile
	 */
	public function registerCssFile($cssFile) {
		$this->cs->registerCssFile($this->assetsUrl . '/' .$cssFile);
	}

	/**
	 * @param string $scriptFile
	 */
	public function registerScriptFile($scriptFile) {
		$this->cs->registerScriptFile($this->assetsUrl . '/' .$scriptFile);
	}

	/**
	 * @param string $coreName
	 */
	public function registerCoreScript($coreName) {
		$this->cs->registerCoreScript($coreName);
	}

	/**
	 * @param string $id
	 * @param string $script
	 * @param string $position
	 * @param array $htmlOptions
	 */
	public function registerScript($id, $script, $position = null, $htmlOptions = array()) {
		switch( $position ) {
			case 'load':
				$position = \CClientScript::POS_LOAD;
				break;
			case 'head':
				$position = \CClientScript::POS_HEAD;
				break;
			case 'end':
				$position = \CClientScript::POS_END;
				break;
			case 'begin':
				$position = \CClientScript::POS_BEGIN;
				break;
			default:
			case 'ready':
				$position = \CClientScript::POS_READY;
				break;
		}
		$this->cs->registerScript($id, $script, $position, $htmlOptions);
	}

	/**
	 * @param string $id
	 * @param string $css
	 * @param string $media
	 */
	public function registerCss($id, $css, $media = '')
	{
		$this->cs->registerCss($id, $css, $media);
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