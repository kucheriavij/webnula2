<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\common;
use webnula2\components\UrlRule;


/**
 * Class Controller
 * @package webnula2\common
 */
class Controller extends \CController {
	/**
	 * @var
	 */
	public $title;
	/**
	 * @var array
	 */
	public $actions = array();
	/**
	 * @var \CAttributeCollection
	 */
	public $params;

	/**
	 * @var array
	 */
	private $rules = array();

	/**
	 * @var string
	 */
	public $assetsUrl = '';

	/**
	 * @var \CClientScript
	 */
	public $cs;

	/**
	 *
	 */
	public function init()
	{
		$this->params = \Yii::app()->urlManager->getParams();
		$this->processRules();

		if( ($assetsPath = \Yii::getPathOfAlias('application.assets')) && is_dir($assetsPath)) {
			$this->assetsUrl = ( YII_DEBUG ?
				\Yii::app()->getAssetManager()->publish( $assetsPath, false, -1, true ) :
				\Yii::app()->getAssetManager()->publish( $assetsPath )
			);
		}
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
	 * @param string $name
	 */
	public function registerPackage($name)
	{
		$this->cs->registerPackage($name);
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
	 * @return CAttributeCollection
	 */
	public function getActionParams()
	{
		return $this->params;
	}

	/**
	 * @return array
	 */
	public function route()
	{
		return array();
	}

	/**
	 *
	 */
	private function processRules()
	{
		$rules = $this->route();
		if( $cache = \Yii::app()->getComponent('cache') )
		{
			$hash=md5(serialize($rules));
			if( ($data=$cache->get(get_class($this))) !== false && isset($data[1]) && $data[1]===$hash )
			{
				$this->rules=$data[0];
				return;
			}
		}

		if( !empty($rules) ) {
			if( is_array($rules) ) {
				foreach( $rules as $pattern => $route ) {
					$this->rules[] = new UrlRule($route, $pattern);
				}
			} else if( is_string($rules) ) {
				$this->rules = $rules;
			}
		}
		if(isset($cache))
			$cache->set(get_class($this),array($this->rules,$hash));
	}

	/**
	 * Run the controller action.
	 */
	public function run($actionID)
	{
		$rawPathInfo = $this->params['routeInfo'];
		if( !empty($this->rules) ) {
			if( is_string($this->rules) )
				$actionID = $this->rules;
			else {
				$manager = \Yii::app()->getUrlManager();
				$request = \Yii::app()->getRequest();
				$pathInfo = trim($rawPathInfo, '/');

				foreach($this->rules as $rule) {
					if(($actionID = $rule->parseUrl($manager, $request, $pathInfo, $rawPathInfo))) {
						$this->params->mergeWith($_GET);
						break;
					}
				}
			}
		} else {
			$action = (string)mb_substr($rawPathInfo, 1, mb_strpos($rawPathInfo, '/', 1)-1);
			if( !empty($action) && $actionID != 'error' ) {
				$actionID = $action;
			}
		}

		// run action
		if(($action=$this->createAction($actionID))!==null) {
			$this->runActionWithFilters($action,$this->filters());
		} else
			$this->missingAction($actionID);
	}

	/**
	 * @param $output
	 */
	public function json($output) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo \CJSON::encode($output);
		\Yii::app()->end();
	}

	/**
	 * @return string
	 */
	public function getCacheKey()
	{
		return get_class($this);
	}
}