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
class Controller extends \CController
{
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
	 * @var string
	 */
	private $_pageTitle;
	/**
	 * @var array
	 */
	private $_titles = array();
	/**
	 * @var array
	 */
	private $_breadcrumbs = array();
	/**
	 * @var bool
	 */
	public $exclude = true;

	/**
	 *
	 */
	public function init()
	{
		$this->params = \Yii::app()->urlManager->getParams();
		$this->processRules();

		$this->cs = \Yii::app()->getClientScript();

		if ( ( $assetsPath = \Yii::getPathOfAlias( 'application.assets' ) ) && is_dir( $assetsPath ) ) {
			$this->assetsUrl = ( YII_DEBUG ? \Yii::app()->getAssetManager()->publish( $assetsPath, false, -1, true ) : \Yii::app()->getAssetManager()->publish( $assetsPath ) );
		}

		if ( isset( $this->section ) ) {
			foreach ( $this->section->cache( 1000 )->getParents( 'publish=1' ) as $parent ) {
				$this->_breadcrumbs[] = array( $parent->title, $parent->url );
			}
			$this->_breadcrumbs[] = array( $this->section->title, $this->section->url );
		} else if ( !empty( \Yii::app()->params->projectName ) ) {
			$this->_breadcrumbs[] = array( \Yii::app()->params->projectName, '/' );
		}

		$this->attachBehavior('global', array(
			'class' => 'application\components\GlobalBehavior'
		));
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset( $name )
	{
		if ( isset( $this->params[$name] ) ) {
			return true;
		} else {
			return parent::__isset( $name );
		}
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name )
	{
		if ( isset( $this->params[$name] ) ) {
			return $this->params[$name];
		}

		return parent::__get( $name );
	}

	/**
	 * @param string $cssFile
	 */
	public function registerCssFile( $cssFile )
	{
		$this->cs->registerCssFile( $this->assetsUrl . '/' . $cssFile );
	}

	/**
	 * @param string $scriptFile
	 */
	public function registerScriptFile( $scriptFile )
	{
		$this->cs->registerScriptFile( $this->assetsUrl . '/' . $scriptFile );
	}

	/**
	 * @param string $coreName
	 */
	public function registerCoreScript( $coreName )
	{
		$this->cs->registerCoreScript( $coreName );
	}

	/**
	 * @param string $name
	 */
	public function registerPackage( $name )
	{
		$this->cs->registerPackage( $name );
	}

	/**
	 * @param string $id
	 * @param string $script
	 * @param string $position
	 * @param array $htmlOptions
	 */
	public function registerScript( $id, $script, $position = null, $htmlOptions = array() )
	{
		switch ( $position ) {
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
		$this->cs->registerScript( $id, $script, $position, $htmlOptions );
	}

	/**
	 * @param string $id
	 * @param string $css
	 * @param string $media
	 */
	public function registerCss( $id, $css, $media = '' )
	{
		$this->cs->registerCss( $id, $css, $media );
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
	 * @param $item
	 */
	public function addLinkItem( $item )
	{
		if ( !is_array( $item ) ) {
			$item = array( $item );
		}
		$this->_breadcrumbs[] = $item;
	}

	/**
	 * @return array
	 */
	public function getLinkItems()
	{
		return $this->_breadcrumbs;
	}

	/**
	 * @return string
	 */
	public function getPageTitle()
	{
		if ( $this->_pageTitle === null ) {
			foreach ( $this->_breadcrumbs as $linkItem ) {
				$this->_titles[] = $linkItem[0];
			}

			$this->_pageTitle = implode( ' :: ', array_reverse( $this->_titles ) );
		}

		return $this->_pageTitle;
	}


	/**
	 *
	 */
	private function processRules()
	{
		$rules = $this->route();
		if ( $cache = \Yii::app()->getComponent( 'cache' ) ) {
			$hash = md5( serialize( $rules ) );
			if ( ( $data = $cache->get( get_class( $this ) ) ) !== false && isset( $data[1] ) && $data[1] === $hash ) {
				$this->rules = $data[0];

				return;
			}
		}

		if ( !empty( $rules ) ) {
			if ( is_array( $rules ) ) {
				foreach ( $rules as $pattern => $route ) {
					$this->rules[] = new UrlRule( $route, $pattern );
				}
			} else if ( is_string( $rules ) ) {
				$this->rules = $rules;
			}
		}
		if ( isset( $cache ) ) {
			$cache->set( get_class( $this ), array( $this->rules, $hash ) );
		}
	}

	/**
	 * Run the controller action.
	 */
	public function run( $actionID )
	{
		$rawPathInfo = $this->params['routeInfo'];

		$beforeActionId = $actionID;
		if ( false === $actionID = $this->processRoute( $rawPathInfo ) ) {
			$actionID = $beforeActionId;
			if ( mb_strrpos( $rawPathInfo, '/' ) !== false ) {
				$action = (string)mb_substr( $rawPathInfo, 1, mb_strpos( $rawPathInfo, '/', 1 ) - 1 );
				if ( !empty( $action ) && $actionID != 'error' ) {
					$actionID = $action;
				}
			}
		}

		if(($action=$this->createAction($actionID))!==null)
		{
			if( $this->beforeAction($action) )
			{
				$this->runActionWithFilters($action,$this->filters());
				$this->afterAction($action);
			}
		} else {
			$this->missingAction( $actionID );
		}
	}

	/**
	 * @param \CAction $action
	 *
	 * @return bool
	 */
	public function beforeAction($action) {
		$this->onBeforeAction(new \CEvent($this, array($action)));

		if(($parent=$this->getModule())===null)
			$parent=Yii::app();

		return $parent->beforeControllerAction($this,$action);
	}

	/**
	 * @param \CEvent $event
	 *
	 * @throws \CException
	 */
	public function onBeforeAction( \CEvent $event ) {
		$this->raiseEvent('onBeforeAction', $event);
	}

	/**
	 * @param \CAction $action
	 */
	public function afterAction($action) {
		$this->onAfterAction(new \CEvent($this, array($action)));

		if(($parent=$this->getModule())===null)
			$parent=Yii::app();
		$parent->afterControllerAction($this,$action);
	}

	/**
	 * @param \CEvent $event
	 *
	 * @throws \CException
	 */
	public function onAfterAction( \CEvent $event ) {
		$this->raiseEvent('onAfterAction', $event);
	}

	/**
	 * @param string|array $tpl_var
	 * @param mixed $value
	 */
	public function assign( $tpl_var, $value = null )
	{
		\Yii::app()->viewRenderer->assign( $tpl_var, $value );
	}

	/**
	 * @param $rawPathInfo
	 *
	 * @return bool
	 */
	private function processRoute( $rawPathInfo )
	{
		if ( !empty( $this->rules ) ) {
			if ( is_string( $this->rules ) ) {
				return $this->rules;
			} else {
				$manager = \Yii::app()->getUrlManager();
				$request = \Yii::app()->getRequest();
				$pathInfo = trim( $rawPathInfo, '/' );
				foreach ( $this->rules as $rule ) {
					if ( ( $r = $rule->parseUrl( $manager, $request, $pathInfo, $rawPathInfo ) ) ) {
						$this->params->mergeWith( $_GET );

						return $r;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param $output
	 */
	public function json( $output )
	{
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-type: application/json' );

		echo \CJSON::encode( $output );
		\Yii::app()->end();
	}

	/**
	 * @return string
	 */
	public function getCacheKey()
	{
		return get_class( $this );
	}
}