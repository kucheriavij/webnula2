<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\modules\cms;


use webnula2\common\WebModule;
use webnula2\components\Kernel;

/**
 * Class CmsModule
 * @package webnula2\modules\cms
 *
 * @Route(rules={
 *  "cms" = "cms",
 *    "cms/auth/login" = "cms/auth/login",
 *    "cms/auth/logout" = "cms/auth/logout",
 *    "cms/section/<uuid:\w+>/<_a:(index|create|update|delete|toggle|tree|sort)>*" = "cms/section/<_a>",
 *    "cms/<_c>/<_a>*" = "cms/<_c>/<_a>",
 *    "cms/<_c>*" = "cms/<_c>"
 * })
 */
class CmsModule extends WebModule
{
	/**
	 * @var string
	 */
	public $defaultController = 'section';
	/**
	 * @var \CClientScript
	 */
	public $cs;
	/**
	 * @var array
	 */
	public $items = array();
	/**
	 * @var array
	 */
	public $returnUrl = array( '/cms/section/index' );
	/**
	 * @var array
	 */
	public $models = array();
	/**
	 * @var
	 */
	private $assetsUrl;
	/**
	 * @var array
	 */
	public $packages = array();

	/**
	 * @var array
	 */
	public $controllerMap = array(
		'section' => array(
			'class' => 'cms\controllers\SectionController'
		),
		'auth' => array(
			'class' => 'cms\controllers\AuthController',
		),
		'default' => array(
			'class' => 'cms\controllers\DefaultController'
		),
		'user' => array(
			'class' => 'cms\controllers\UserController',
		),
		'groups' => array(
			'class' => 'cms\controllers\GroupsController'
		),
		'image' => array(
			'class' => 'cms\controllers\ImageController',
		),
		'file' => array(
			'class' => 'cms\controllers\FileController',
		),
		'tinymce' => array(
			'class' => 'cms\controllers\TinyMceController',
		),
		'elfinder' => array(
			'class' => 'cms\controllers\ElfinderController',
		),
		'menu' => array(
			'class' => 'cms\controllers\MenuController'
		)
	);

	/**
	 * Initialize CmsModule.
	 */
	protected function init()
	{
		parent::init();

		$this->setImport( array(
			'zii.widgets.*',
			'zii.widgets.grid.*'
		) );

		\Yii::app()->getUser()->loginUrl = array( '/cms/auth/login' );
		\Yii::app()->getUser()->returnUrl = $this->returnUrl;
		\Yii::app()->errorHandler->errorAction = 'cms/default/error';

		$this->cs = \Yii::app()->getClientScript();

		\Yii::setPathOfAlias( 'cms@layouts', $this->getViewPath() . '/layouts' );

		if(is_array($packages = include dirname(__FILE__).'/packages.php')) {
			$packages = \CMap::mergeArray($this->packages, $packages);
		}

		$angular = array(
			'cms.common',
			'cms.fileuploader',
			'cms.imagesuploader',
			'cms.translite',
		);
		foreach( $this->packages as $name => $def ) {
			if( isset($def['js']) && isset($def['angular']) && $def['angular'] === true ) {
				unset($def['angular']);
				$angular[] = $name;
			}
		}

		$modules = $this->getModules();
		foreach ( $packages as $name => $definition ) {
			if ( isset( $definition['baseUrl'] ) ) {
				if ( preg_match( '!(\w+):assets!i', $definition['baseUrl'], $match ) && isset( $modules[$match[1]] ) ) {
					$basePath = dirname( \Yii::getPathOfAlias(str_replace('\\', '.', $modules[$match[1]]['class']) ));
					$definition['baseUrl'] =  ( YII_DEBUG ?
						\Yii::app()->getAssetManager()->publish( $basePath . '/assets', false, -1, true ) :
						\Yii::app()->getAssetManager()->publish( $basePath . '/assets' )
					);
				}
			}

			$this->cs->addPackage( $name, $definition );
			$this->cs->registerPackage( $name );
		}

		$this->cs->registerScript(__CLASS__, sprintf("angular.module('cms', %s);", \CJavaScript::encode($angular)), \CClientScript::POS_END);

		\Yii::app()->viewRenderer->assign( array(
			'cms' => $this,
			'moduleClass' => get_class( $this )
		) );

		$this->processModels();
	}


	/**
	 * @param $path
	 * @param string $suffix
	 *
	 * @return string
	 */
	public static function basename( $path, $suffix = '' )
	{
		if ( ( $len = mb_strlen( $suffix ) ) > 0 && mb_substr( $path, -$len ) == $suffix ) {
			$path = mb_substr( $path, 0, -$len );
		}
		$path = rtrim( str_replace( '\\', '/', $path ), '/\\' );
		if ( ( $pos = mb_strrpos( $path, '/' ) ) !== false ) {
			return mb_substr( $path, $pos + 1 );
		}

		return $path;
	}

	/**
	 *
	 */
	public function menuItems()
	{
		$items = array(
			array( 'label' => self::t( 'Structure' ), 'url' => array( '/cms/section/index' )  ),
			array( 'label' => self::t( 'Menu' ), 'url' => array( '/cms/menu/index' )  ),
			array( 'label' => self::t( 'Management users' ), 'url' => "#", 'items' => array(
				array( 'label' => self::t( 'Users' ), 'url' => array( '/cms/user/index' )  ),
				array( 'label' => self::t( 'Groups' ), 'url' => array( '/cms/groups/index' )  )
			) ),
		);

		foreach ( $this->items as $item ) {
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @param \CController $controller
	 * @param \CAction $action
	 *
	 * @return bool
	 */
	public function beforeControllerAction( $controller, $action )
	{
		if ( \Yii::app()->getUser()->checkAccess( 'Administrator' ) ) {
			return true;
		} else if( $controller->id !== 'auth' ) {
			\Yii::app()->getUser()->loginRequired();
			return false;
		}
		return true;
	}

	/**
	 * @param $cssFile
	 */
	public function registerCssFile( $cssFile )
	{
		$this->cs->registerCssFile( $this->getAssetsUrl() . '/' . $cssFile );
	}

	/**
	 * @param $jsFile
	 */
	public function registerScriptFile( $jsFile )
	{
		$this->cs->registerScriptFile( $this->getAssetsUrl() . '/' . $jsFile );
	}

	/**
	 *
	 */
	private function processModels()
	{
		$modules = \Yii::app()->getModules();
		foreach ( $this->models as $model => $title ) {
			list( $id, $className ) = explode( ':', $model, 2 );
			if ( isset( $modules[$id] ) ) {
				$className = sprintf( "%s\models\%s", $id, $className );
				$this->models[$className] = $title;
				unset( $this->models[$model] );
			}
		}
	}

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public static function dirname( $path )
	{
		$pos = mb_strrpos( str_replace( '\\', '/', $path ), '/' );
		if ( $pos !== false ) {
			return mb_substr( $path, 0, $pos );
		} else {
			return '';
		}
	}
}