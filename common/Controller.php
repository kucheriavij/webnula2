<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\common;


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
	 * @var CAttributeCollection
	 */
	public $params;

	private $rules = array();

	/**
	 *
	 */
	public function init()
	{
		$this->params = \Yii::app()->urlManager->getParams();
		$this->processRules();
	}

	public function getActionParams()
	{
		return $this->params;
	}

	public function route()
	{
		return array();
	}

	private function processRules()
	{
		$rules = $this->route();

		if( ($cache=\Yii::app()->getComponent('cache')) !== null )
		{
			$hash=md5(serialize($rules));
			if( ($data=$cache->get($this->cacheKey)) !== false && isset($data[1]) && $data[1]===$hash )
			{
				$this->rules=$data[0];
				return;
			}
		}

		if( !empty($rules) ) {
			if( is_array($rules) ) {
				foreach( $rules as $pattern => $route ) {
					$this->rules[] = new \CUrlRule($route, $pattern);
				}
			} else if( is_string($rules) ) {
				$this->rules = $rules;
			}
		}
		if(isset($cache))
			$cache->set($this->cacheKey,array($this->_rules,$hash));
	}

	/**
	 * Run the controller action.
	 */
	public function run($actionID)
	{
		$rawPathInfo = $this->params['routeInfo'];

		if( !empty($this->_rules) ) {
			if( is_string($this->_rules) )
				$actionID = $this->_rules;
			else {
				$manager = \Yii::app()->getUrlManager();
				$request = \Yii::app()->getRequest();
				$pathInfo = trim($rawPathInfo, '/');

				foreach($this->_rules as $rule) {
					if(($actionID = $rule->parseUrl($manager, $request, $pathInfo, $rawPathInfo)))
						break;
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

	public function json($output) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo \CJSON::encode($output);
		\Yii::app()->end();
	}

	public function getCacheKey()
	{
		return get_class($this);
	}
}