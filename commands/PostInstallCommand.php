<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\commands;


class PostInstallCommand extends \CConsoleCommand {
	public function actionIndex($nocommit=false)
	{
		$aliases = array(
			array('webroot.protected.commands', 'chmod' => 0755),
			array('webroot.protected.components', 'chmod' => 0755),
			array('webroot.protected.migrations', 'chmod' => 0755),
			array('webroot.protected.messages', 'chmod' => 0755),
			array('webroot.protected.runtime', 'chmod' => 0777),
			array('webroot.media', 'chmod' => 0777),
			array('webroot.assets', 'chmod' => 0777),
		);

		foreach( $aliases as $alias ) {
			$dir = \Yii::getPathOfAlias($alias[0]);
			if( !is_dir($dir) ) {
				mkdir($dir, $alias['chmod'], true);
				chmod($dir, $alias['chmod']);
			} else {
				chmod($dir, $alias['chmod']);
			}
		}

		if( $nocommit ) {
			if ( $vendorPath = \Yii::getPathOfAlias( 'webroot.vendor' ) ) {
				$iterator = new \RegexIterator(
					new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator( $vendorPath ),
						\RecursiveIteratorIterator::SELF_FIRST
					),
					'/.+(\\\|\/)(?:\.(git|svn))$/i',
					\RecursiveRegexIterator::GET_MATCH
				);

				$dirs = iterator_to_array( $iterator );
				foreach ( $dirs as $dirName => $dirConf ) {
					\CFileHelper::removeDirectory( $dirName );
				}
			}
		}
	}
} 