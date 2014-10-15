<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\commands;


class SchemaCommand extends \CConsoleCommand {
	public function actionUpdate()
	{
		\Yii::app()->schematool->updateCommand();
	}

	public function actionCreate($class) {

	}
} 