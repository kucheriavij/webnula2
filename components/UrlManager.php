<?php
/**
 * Url Manager - Adapted from Yii project.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\components;

use webnula2\models\Section;

/**
 * Class UrlManager
 * @package webnula2\components
 */
final class UrlManager extends \CApplicationComponent
{
	/**
	 *
	 */
	const CACHE_KEY = 'Webnula2.UrlManager.rules';

	/**
	 * @var string
	 */
	public $urlSuffix = '';
	/**
	 * @var bool
	 */
	public $showScriptName = false;
	/**
	 * @var bool
	 */
	public $appendParams = true;
	/**
	 * @var bool
	 */
	public $caseSensitive = true;
	/**
	 * @var string
	 */
	public $cacheID = 'cache';
	/**
	 * @var bool
	 */
	public $useStrictParsing = false;
	/**
	 * @var bool
	 */
	public $matchValue = false;

	/**
	 * @var array|UrlRule[]
	 */
	private $_rules = array();
	/**
	 * @var
	 */
	private $_baseUrl;
	/**
	 * @var \CAttributeCollection
	 */
	private $_params;
	/**
	 * @var Annotation
	 */
	private $_annot;

	/**
	 * Initializes the application component.
	 */
	public function init()
	{
		parent::init();

		$this->_annot = \Yii::app()->getComponent('annotation');
		$this->processRules();
	}

	/**
	 * Processes the URL rules.
	 */
	protected function processRules()
	{
		if ( $this->cacheID !== false && ( $cache = \Yii::app()->getComponent( $this->cacheID ) ) !== null ) {
			if ( ( $data = $cache->get( self::CACHE_KEY ) ) !== false ) {
				$this->_rules = $data;
				return;
			}
		}
		$this->applyRules(\Yii::app()->getModules());

		if ( isset( $cache ) )
			$cache->set( self::CACHE_KEY, $this->_rules );
	}

	/**
	 * @param $modules
	 */
	private function applyRules($modules)
	{
		foreach ( $modules as $id => $config ) {
			if ( !isset( $config['enabled'] ) || $config['enabled'] ) {
				$class = new \ReflectionClass(\Yii::import($config['class'], false));
				$classAnnot = $this->_annot->getClassAnnotations($class);

				if( isset($classAnnot['Route']) ) {
					foreach($classAnnot['Route']->rules as $pattern => $route) {
						$this->_rules[]=$this->createUrlRule($route,$pattern);
					}
				}
			}
		}
	}

	/**
	 * @param $route
	 * @param $pattern
	 *
	 * @return mixed
	 * @throws \CException
	 */
	protected function createUrlRule( $route, $pattern )
	{
		if ( is_array( $route ) && isset( $route['class'] ) )
			return $route;
		else {
			return new UrlRule( $route, $pattern );
		}
	}

	/**
	 * @param $rules
	 * @param bool $append
	 */
	public function addRules( $rules, $append = true )
	{
		if ( $append ) {
			foreach ( $rules as $pattern => $route )
				$this->_rules[] = $this->createUrlRule( $route, $pattern );
		} else {
			$rules = array_reverse( $rules );
			foreach ( $rules as $pattern => $route )
				array_unshift( $this->_rules, $this->createUrlRule( $route, $pattern ) );
		}
	}

	/**
	 * @param $route
	 * @param array $params
	 * @param string $ampersand
	 *
	 * @return string
	 * @throws \CException
	 */
	public function createUrl( $route, $params = array(), $ampersand = '&' )
	{
		foreach ( $params as $i => $param )
			if ( $param === null )
				$params[$i] = '';

		if ( isset( $params['#'] ) ) {
			$anchor = '#' . $params['#'];
			unset( $params['#'] );
		} else
			$anchor = '';

		if ( $route instanceof Section ) {
			$url = $route->url;
			if ( $route !== '' )
				$url .= $this->urlSuffix;
			$query = $this->createPathInfo( $params, '=', $ampersand );

			return $query === '' ? $url : $url . '?' . $query;
		} else {
			$route = trim( $route, '/' );
			foreach ( $this->_rules as $i => $rule ) {
				if ( is_array( $rule ) )
					$this->_rules[$i] = $rule = \Yii::createComponent( $rule );
				if ( ( $url = $rule->createUrl( $this, $route, $params, $ampersand ) ) !== false ) {
					if ( $rule->hasHostInfo )
						return $url === '' ? '/' . $anchor : $url . $anchor;
					else
						return $this->getBaseUrl() . '/' . $url . $anchor;
				}
			}

			return $this->createUrlDefault( $route, $params, $ampersand ) . $anchor;
		}
	}

	/**
	 * @param $params
	 * @param $equal
	 * @param $ampersand
	 * @param null $key
	 *
	 * @return string
	 */
	public function createPathInfo( $params, $equal, $ampersand, $key = null )
	{
		$pairs = array();
		foreach ( $params as $k => $v ) {
			if ( $key !== null )
				$k = $key . '[' . $k . ']';

			if ( is_array( $v ) )
				$pairs[] = $this->createPathInfo( $v, $equal, $ampersand, $k );
			else
				$pairs[] = urlencode( $k ) . $equal . urlencode( $v );
		}

		return implode( $ampersand, $pairs );
	}

	/**
	 * @return mixed
	 */
	public function getBaseUrl()
	{
		if ( $this->_baseUrl !== null )
			return $this->_baseUrl;
		else {
			if ( $this->showScriptName )
				$this->_baseUrl = \Yii::app()->getRequest()->getScriptUrl();
			else
				$this->_baseUrl = \Yii::app()->getRequest()->getBaseUrl();

			return $this->_baseUrl;
		}
	}

	/**
	 * @param $value
	 */
	public function setBaseUrl( $value )
	{
		$this->_baseUrl = $value;
	}

	/**
	 * @param $route
	 * @param $params
	 * @param $ampersand
	 *
	 * @return string
	 */
	protected function createUrlDefault( $route, $params, $ampersand )
	{
		$url = rtrim( $this->getBaseUrl() . '/' . $route, '/' );
		if ( $this->appendParams ) {
			$url = rtrim( $url . '/' . $this->createPathInfo( $params, '/', '/' ), '/' );

			return $route === '' ? $url : $url . $this->urlSuffix;
		} else {
			if ( $route !== '' )
				$url .= $this->urlSuffix;
			$query = $this->createPathInfo( $params, '=', $ampersand );

			return $query === '' ? $url : $url . '?' . $query;
		}
	}

	/**
	 * Returns user-defined parameters.
	 * @return CAttributeCollection the list of user-defined parameters
	 */
	public function getParams()
	{
		if($this->_params!==null)
			return $this->_params;
		else
		{
			$this->_params=new \CAttributeCollection;
			$this->_params->caseSensitive=true;
			return $this->_params;
		}
	}

	/**
	 * Sets user-defined parameters.
	 * @param array $value user-defined parameters. This should be in name-value pairs.
	 */
	private function setParams($value)
	{
		$params=$this->getParams();
		foreach($value as $k=>$v)
			$params->add($k,$v);
	}

	/**
	 * @return bool
	 */
	public function hasParams()
	{
		return $this->getParams()->count() > 0;
	}

	/**
	 * @param $request
	 *
	 * @throws \CException
	 * @throws \CHttpException
	 */
	public function parseUrl( $request )
	{
		$rawPathInfo = $request->getPathInfo();
		$pathInfo = $this->removeUrlSuffix( $rawPathInfo, $this->urlSuffix );
		foreach ( $this->_rules as $i => $rule ) {
			if ( is_array( $rule ) )
				$this->_rules[$i] = $rule = \Yii::createComponent( $rule );
			if ( ( $r = $rule->parseUrl( $this, $request, $pathInfo, $rawPathInfo ) ) !== false ) {
				$this->setParams( $_GET );
				return $r;
			}
		}

		$route = trim( $pathInfo, '/' );
		$segments = array();

		$segments[] = '/' . $route . '/';
		while ( ( $pos = strrpos( $route, '/' ) ) !== false ) {
			$route = substr( $route, 0, $pos );
			$segments[] = '/' . $route . '/';
		}
		$segments[] = '/';


		$criteria = new \CDbCriteria();
		$criteria->compare('publish', 1);
		$criteria->order = 'level DESC, left_key DESC';
		$criteria->addInCondition( 'url', $segments );

		if ( null !== $section = Section::model()->find( $criteria ) ) {
			$this->getParams()->add('routeInfo', str_replace($section->url, '/', '/'.$rawPathInfo.'/'));
			$this->getParams()->add('section', $section);

			if( !empty($section->r_url) ) {
				\Yii::app()->getRequest()->redirect($section->r_url);
			}
			return $section->route;
		}
		throw new \CHttpException( 404, \Yii::t( 'yii', 'Unable to resolve the request "{route}".',
			array( '{route}' => $pathInfo ) ) );
	}

	/**
	 * @param $pathInfo
	 * @param $urlSuffix
	 *
	 * @return string
	 */
	public function removeUrlSuffix( $pathInfo, $urlSuffix )
	{
		if ( $urlSuffix !== '' && substr( $pathInfo, -strlen( $urlSuffix ) ) === $urlSuffix )
			return substr( $pathInfo, 0, -strlen( $urlSuffix ) );
		else
			return $pathInfo;
	}

	/**
	 * @param $pathInfo
	 */
	public function parsePathInfo( $pathInfo )
	{
		if ( $pathInfo === '' )
			return;
		$segs = explode( '/', $pathInfo . '/' );
		$n = count( $segs );
		for ( $i = 0; $i < $n - 1; $i += 2 ) {
			$key = $segs[$i];
			if ( $key === '' ) continue;
			$value = $segs[$i + 1];
			if ( ( $pos = strpos( $key, '[' ) ) !== false && ( $m = preg_match_all( '/\[(.*?)\]/', $key, $matches ) ) > 0 ) {
				$name = substr( $key, 0, $pos );
				for ( $j = $m - 1; $j >= 0; --$j ) {
					if ( $matches[1][$j] === '' )
						$value = array( $value );
					else
						$value = array( $matches[1][$j] => $value );
				}
				if ( isset( $_GET[$name] ) && is_array( $_GET[$name] ) )
					$value = \CMap::mergeArray( $_GET[$name], $value );
				$_REQUEST[$name] = $_GET[$name] = $value;
			} else
				$_REQUEST[$key] = $_GET[$key] = $value;
		}
	}
}