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
	 * @var bool
	 */
	private $_initialized=false;

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

		$Yii->params->add( 'imageSizes', array('t260x180' => array(
			'method' => 'cropInset',
			'width' => 260,
			'height' => 180,
		) ) );

		if ( \Yii::app() instanceof \CWebApplication ) {
			\Yii::setPathOfAlias( 'layouts', $Yii->getViewPath() . '/layouts' );
		}
	}
} 