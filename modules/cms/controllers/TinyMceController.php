<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;


/**
 * Class TinyMceController
 * @package cms\controllers
 */
class TinyMceController extends \CController
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
				'actions' => array( 'compressor', 'spellchecker' ),
				'roles' => array( 'Administrator' ) ),
			array( 'deny' )
		);
	}


	/**
	 * @return array
	 */
	public function actions()
	{
		return array(
			'compressor' => array(
				'class' => 'webnula2.extensions.tinymce.TinyMceCompressorAction',
				'settings' => array(
					'compress' => true,
					'disk_cache' => true,
				)
			),
			'spellchecker' => array(
				'class' => 'webnula2.extensions.tinymce.TinyMceSpellcheckerAction',
			),
		);
	}
} 