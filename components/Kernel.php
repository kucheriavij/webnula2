<?php
/**
 * Webnula2 kernel - application component to preloaded of all webnula2 component.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\components;

/**
 * Class Kernel
 * @package webnula2\components
 */
final class Kernel extends \CComponent implements \IApplicationComponent
{
	/**
	 * @var array
	 */
	public $behaviors = array();

	/**
	 * @var array
	 */
	public $relations = array();

	/**
	 * @var bool
	 */
	private $_initialized=false;

	/**
	 * @var Kernel
	 */
	private static $_instance = null;

	/**
	 * @var array
	 */
	private $packages = array();

	/**
	 * @var array
	 */
	private $assets = array();

	/**
	 * @var int
	 */
	public $assetsCacheDuration = 300;

	/**
	 * @var \CClientScript
	 */
	private $cs;

	/**
	 *
	 */
	const MODULES_ASSETS_KEY = 'Webnula2.Modules.Assets';

	/**
	 * @return Kernel
	 */
	public static function get()
	{
		if ( null === self::$_instance ) {
			// Still nothing?
			if ( null === self::$_instance ) {
				if ( \Yii::app()->hasComponent( 'webnula2' ) ) {
					self::$_instance = \Yii::app()->getComponent( 'webnula2' );
				}
			}
		}

		return self::$_instance;
	}

	/**
	 * Checks if this application component has been initialized.
	 * @return boolean whether this application component has been initialized (ie, {@link init()} is invoked).
	 */
	public function getIsInitialized()
	{
		return $this->_initialized;
	}

	/**
	 * Initializes the application component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application.
	 */
	public function init()
	{
		$this->_initialized=true;

		$Yii = \Yii::app();

		$Yii->setComponents( array(
			'schematool' => array(
				'class' => 'webnula2\components\SchemaTool'
			),
			'annotation' => array(
				'class' => 'webnula2\components\Annotation',
				'namespaces' => array(
					'webnula2\components',
					'webnula2\common',
					'webnula2\orm',
				)
			)
		) );

		$Yii->params['imageSizes'] = array_merge($Yii->params['imageSizes'], array('t260x180' => array(
			'method' => 'cropInset',
			'width' => 260,
			'height' => 180,
		) ) );

		if ( $Yii instanceof \CWebApplication ) {
			\Yii::setPathOfAlias( 'layouts', $Yii->getViewPath() . '/layouts' );

			$this->cs = $Yii->getClientScript();

			$this->prepare($Yii);

			$Yii->viewRenderer->assign(array(
				'kernel' => $this,
			));
		}

		foreach( $Yii->getModules() as $id => $config )
		{
			$basePath = dirname(\Yii::getPathOfAlias(str_replace('\\', '.', $config['class'])));
			\Yii::setPathOfAlias("$id", $basePath);
		}
	}

	private function prepare(\CWebApplication $Yii)
	{
		$modules = $Yii->getModules();

		$this->assets['app'] = ( YII_DEBUG ?
			$Yii->getAssetManager()->publish( \Yii::getPathOfAlias('application.assets'), false, -1, true ) :
			$Yii->getAssetManager()->publish( \Yii::getPathOfAlias('application.assets') )
		);

		$packages = \Yii::getPathOfAlias('application.config.packages');
		$packages = include $packages.'.php';

		foreach ( $packages as $name => $definition ) {
			$preload = isset( $definition['preload'] ) && $definition['preload'] === true;
			if ( isset( $definition['baseUrl'] ) ) {
				if ( preg_match( '!(\w+):assets!i', $definition['baseUrl'], $match ) && isset( $modules[$match[1]] ) ) {
					$basePath = dirname( \Yii::getPathOfAlias(str_replace('\\', '.', $modules[$match[1]]['class']) ));
					$definition['baseUrl'] = $this->assets[$match[1]] = ( YII_DEBUG ?
						$Yii->getAssetManager()->publish( $basePath . '/assets', false, -1, true ) :
						$Yii->getAssetManager()->publish( $basePath . '/assets' )
					);
				}
			} else {
				$definition['baseUrl'] = $this->assets['app'];
			}
			unset( $definition['preload'] );

			$this->cs->addPackage( $name, $definition );
			if ( $preload )
				$this->cs->registerPackage( $name );
			$packages[$name] = $definition;
		}
	}

	/**
	 * @param string $id
	 *
	 * @return null|string
	 */
	public function getAsset($id)
	{
		return isset($this->assets[$id]) ? $this->assets[$id] : null;
	}

	/**
	 * @param string $id
	 * @param string $name
	 *
	 * @throws \CException
	 */
	public function registerPackage( $name ) {
		$this->cs->registerPackage($name);
	}
}