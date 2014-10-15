<?php
/**
 * Webnula2 kernel - application component to preloaded of all webnula2 component.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\components;

final class Kernel extends \CApplicationComponent
{
	public function init()
	{
		parent::init();

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

		if( !isset($Yii->params->imageSizes) ) {
			$Yii->params->add( 'imageSizes', array('t260x180' => array(
				'method' => 'cropInset',
				'width' => 260,
				'height' => 180,
			) ) );
		}

		if ( \Yii::app() instanceof \CWebApplication ) {
			\Yii::setPathOfAlias( 'layouts', $Yii->getViewPath() . '/layouts' );
		}
	}
} 