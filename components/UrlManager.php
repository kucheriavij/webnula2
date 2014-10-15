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
	const CACHE_KEY = 'Yii.CUrlManager.rules';

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
			$hash = md5( serialize( $this->rules ) );
			if ( ( $data = $cache->get( self::CACHE_KEY ) ) !== false && isset( $data[1] ) && $data[1] === $hash ) {
				$this->_rules = $data[0];

				return;
			}
		}

		$this->applyRules(\Yii::app()->getModules());

		if ( isset( $cache ) )
			$cache->set( self::CACHE_KEY, array( $this->_rules, $hash ) );
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
			if ( $this->appendParams ) {
				$url = rtrim( $url . '/' . $this->createPathInfo( $params, '/', '/' ), '/' );

				return $url === '' ? $url : $url . $this->urlSuffix;
			} else {
				if ( $route !== '' )
					$url .= $this->urlSuffix;
				$query = $this->createPathInfo( $params, '=', $ampersand );

				return $query === '' ? $url : $url . '?' . $query;
			}
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
		$criteria->addInCondition( 'url', $segments );
		if ( null !== $section = Section::model()->find( $criteria ) ) {
			$this->getParams()->add('routeInfo', str_replace($section->url, '/', $pathInfo));
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

class UrlRule extends \CComponent
{
	public $urlSuffix;
	public $caseSensitive;
	public $defaultParams=array();
	public $verb;
	public $parsingOnly=false;
	public $route;
	public $references=array();
	public $routePattern;
	public $pattern;
	public $template;
	public $params=array();
	public $append;
	public $hasHostInfo;

	public function __construct($route,$pattern)
	{
		if(is_array($route))
		{
			foreach(array('urlSuffix', 'caseSensitive', 'defaultParams', 'verb', 'parsingOnly') as $name)
			{
				if(isset($route[$name]))
					$this->$name=$route[$name];
			}
			if(isset($route['pattern']))
				$pattern=$route['pattern'];
			$route=$route[0];
		}
		$this->route=trim($route,'/');

		$tr2['/']=$tr['/']='\\/';

		if(strpos($route,'<')!==false && preg_match_all('/<(\w+)>/',$route,$matches2))
		{
			foreach($matches2[1] as $name)
				$this->references[$name]="<$name>";
		}

		$this->hasHostInfo=!strncasecmp($pattern,'http://',7) || !strncasecmp($pattern,'https://',8);

		if($this->verb!==null)
			$this->verb=preg_split('/[\s,]+/',strtoupper($this->verb),-1,PREG_SPLIT_NO_EMPTY);

		if(preg_match_all('/<(\w+):?(.*?)?>/',$pattern,$matches))
		{
			$tokens=array_combine($matches[1],$matches[2]);
			foreach($tokens as $name=>$value)
			{
				if($value==='')
					$value='[^\/]+';
				$tr["<$name>"]="(?P<$name>$value)";
				if(isset($this->references[$name]))
					$tr2["<$name>"]=$tr["<$name>"];
				else
					$this->params[$name]=$value;
			}
		}
		$p=rtrim($pattern,'*');
		$this->append=$p!==$pattern;
		$p=trim($p,'/');
		$this->template=preg_replace('/<(\w+):?.*?>/','<$1>',$p);
		$this->pattern='/^'.strtr($this->template,$tr).'\/';
		if($this->append)
			$this->pattern.='/u';
		else
			$this->pattern.='$/u';

		if($this->references!==array())
			$this->routePattern='/^'.strtr($this->route,$tr2).'$/u';
		if(YII_DEBUG && @preg_match($this->pattern,'test')===false)
			throw new \CException(\Yii::t('yii','The URL pattern "{pattern}" for route "{route}" is not a valid regular expression.',
				array('{route}'=>$route,'{pattern}'=>$pattern)));
	}

	public function createUrl(UrlManager $manager,$route,$params,$ampersand)
	{
		if($this->parsingOnly)
			return false;

		if($manager->caseSensitive && $this->caseSensitive===null || $this->caseSensitive)
			$case='';
		else
			$case='i';

		$tr=array();
		if($route!==$this->route)
		{
			if($this->routePattern!==null && preg_match($this->routePattern.$case,$route,$matches))
			{
				foreach($this->references as $key=>$name)
					$tr[$name]=$matches[$key];
			}
			else
				return false;
		}

		foreach($this->defaultParams as $key=>$value)
		{
			if(isset($params[$key]))
			{
				if($params[$key]==$value)
					unset($params[$key]);
				else
					return false;
			}
		}

		foreach($this->params as $key=>$value)
			if(!isset($params[$key]))
				return false;

		foreach($this->params as $key=>$value)
		{
			$tr["<$key>"]=urlencode($params[$key]);
			unset($params[$key]);
		}

		$suffix=$this->urlSuffix===null ? $manager->urlSuffix : $this->urlSuffix;

		$url=strtr($this->template,$tr);

		if($this->hasHostInfo)
		{
			$hostInfo=\Yii::app()->getRequest()->getHostInfo();
			if(stripos($url,$hostInfo)===0)
				$url=substr($url,strlen($hostInfo));
		}

		if(empty($params))
			return $url!=='' ? $url.$suffix : $url;

		if($this->append)
			$url.='/'.$manager->createPathInfo($params,'/','/').$suffix;
		else
		{
			if($url!=='')
				$url.=$suffix;
			$url.='?'.$manager->createPathInfo($params,'=',$ampersand);
		}

		return $url;
	}

	public function parseUrl(UrlManager $manager,$request,$pathInfo,$rawPathInfo)
	{
		if($this->verb!==null && !in_array($request->getRequestType(), $this->verb, true))
			return false;

		if($manager->caseSensitive && $this->caseSensitive===null || $this->caseSensitive)
			$case='';
		else
			$case='i';

		if($this->urlSuffix!==null)
			$pathInfo=$manager->removeUrlSuffix($rawPathInfo,$this->urlSuffix);

		// URL suffix required, but not found in the requested URL
		if($manager->useStrictParsing && $pathInfo===$rawPathInfo)
		{
			$urlSuffix=$this->urlSuffix===null ? $manager->urlSuffix : $this->urlSuffix;
			if($urlSuffix!='' && $urlSuffix!=='/')
				return false;
		}

		if($this->hasHostInfo)
			$pathInfo=strtolower($request->getHostInfo()).rtrim('/'.$pathInfo,'/');

		$pathInfo.='/';
		if(preg_match($this->pattern.$case,$pathInfo,$matches)) {
			foreach ( $this->defaultParams as $name => $value ) {
				if ( !isset( $_GET[$name] ) )
					$_REQUEST[$name] = $_GET[$name] = $value;
			}
			$tr = array();
			foreach ( $matches as $key => $value ) {
				if ( isset( $this->references[$key] ) )
					$tr[$this->references[$key]] = $value;
				elseif ( isset( $this->params[$key] ) )
					$_REQUEST[$key] = $_GET[$key] = $value;
			}

			if ( $pathInfo !== $matches[0] ) // there're additional GET params
				$manager->parsePathInfo( ltrim( substr( $pathInfo, strlen( $matches[0] ) ), '/' ) );
			if ( $this->routePattern !== null ) {
				return strtr( $this->route, $tr );
			} else {
				return $this->route;
			}
		}  else {
			return false;
		}
	}
}