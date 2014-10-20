<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\commands;


class CacheCommand extends \CConsoleCommand {
	public function actionFlush()
	{
		if( $cache = \Yii::app()->getComponent('cache') ) {
			$cache->flush();
		}
	}
}