<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\modules\cms;


use webnula2\common\WebModule;

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

		$packages = require_once dirname(__FILE__).'/packages.php';
		foreach ( $packages as $name => $definition ) {
			$this->cs->addPackage( $name, $definition );
			$this->cs->registerPackage($name);
		}

		\Yii::setPathOfAlias( 'cms@layouts', $this->getViewPath() . '/layouts' );

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
			array( 'label' => self::t( 'Structure' ), 'url' => array( 'section/index' )  ),
			array( 'label' => self::t( 'Menu' ), 'url' => array( 'menu/index' )  ),
			array( 'label' => self::t( 'Management users' ), 'url' => "#", 'items' => array(
				array( 'label' => self::t( 'Users' ), 'url' => array( 'user/index' )  ),
				array( 'label' => self::t( 'Groups' ), 'url' => array( 'groups/index' )  )
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
		if ( !\Yii::app()->getUser()->checkAccess( 'Administrator' ) && ( $controller->id !== 'auth' && $action->id != 'login' ) ) {
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
				$className = sprintf( "%s\models\%s", self::dirname( $modules[$id]['class'] ), $className );
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