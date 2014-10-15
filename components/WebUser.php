<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\components;


use webnula2\models\User;

/**
 * Class WebUser
 * @package webnula2\components
 */
class WebUser extends \CWebUser
{
	/**
	 * @var webnula2\models\User
	 */
	private $_model;

	/**
	 * @param $name
	 *
	 * @return mixed|null|void
	 */
	public function get( $name )
	{
		$model = $this->getModel();

		return isset( $model->$name ) ? $model->$name : null;
	}

	/**
	 * @return webnula2\models\User
	 */
	private function getModel()
	{
		if ( $this->_model === null ) {
			$this->_model = User::model()->findByPk( $this->id );
		}

		return $this->_model;
	}
} 