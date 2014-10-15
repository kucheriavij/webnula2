<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace cms\controllers;


/**
 * Class ElfinderController
 * @package cms\controllers
 */
class ElfinderController extends \CController
{
	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			'accessControl'
		);
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'connector', 'elfinderTinyMce', 'elfinderFileInput' ),
				'roles' => array( 'Administrator' ) ),
			array( 'deny' )
		);
	}

	/**
	 * @return array
	 */
	public function actions()
	{
		$mediaPath = \Yii::getPathOfAlias( 'webroot.media.media' );
		if ( !is_dir( $mediaPath ) ) {
			mkdir( $mediaPath, 0777, true );
			chmod( $mediaPath, 0777 );
		}

		return array(
			// main action for elFinder connector
			'connector' => array(
				'class' => 'webnula2.extensions.elfinder.ElFinderConnectorAction',
				// elFinder connector configuration
				// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
				'settings' => array(
					'roots' => array(
						array(
							'driver' => 'LocalFileSystem',
							'path' => \Yii::getPathOfAlias( 'webroot.media.media' ),
							'URL' => '/media/media/',
							'alias' => \Yii::t( 'webnula2.locale', 'Home' ),
							'acceptedName' => '/^[^\.].*$/', // disable creating dotfiles
							'attributes' => array(
								array(
									'pattern' => '/\/[.].*$/', // hide dotfiles
									'read' => false,
									'write' => false,
									'hidden' => true,
								),
							),
						)
					),
				)
			),
			// action for TinyMCE popup with elFinder widget
			'elfinderTinyMce' => array(
				'class' => 'webnula2.extensions.elfinder.TinyMceElFinderPopupAction',
				'connectorRoute' => 'connector', // main connector action id
			),
			// action for file input popup with elFinder widget
			'elfinderFileInput' => array(
				'class' => 'webnula2.extensions.elfinder.ServerFileInputElFinderPopupAction',
				'connectorRoute' => 'connector', // main connector action id
			),
		);
	}
}