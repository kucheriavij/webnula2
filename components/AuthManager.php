<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\components;


use webnula2\models\AuthItem;

class AuthManager extends \CDbAuthManager
{
	public function getAuthItem( $item )
	{
		if ( $item instanceof AuthItem ) {
			if ( ( $data = @unserialize( $item->data ) ) === false )
				$data = null;

			return new \CAuthItem( $this, $item->name, $item->type, $item->description, $item->bizrule, $data );
		} else {
			return parent::getAuthItem( $item );
		}
	}
} 