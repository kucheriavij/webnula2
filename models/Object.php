<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;


/**
 * Class Object
 * @package webnula2\models
 */
abstract class Object extends Entity
{
	/**
	 * @Column(type="integer")
	 * @BelongsTo(target="webnula2\models\Section", reference = "id")
	 */
	protected $_parent_id;


	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'ar-relation' => 'webnula2.extensions.EActiveRecordRelationBehavior',
		);
	}

	/**
	 * do not do anything with relations defined with 'through' or have limiting 'condition'/'scopes' defined
	 *
	 * @param array $relation
	 * @return bool
	 */
	protected function isRelationSupported($relation)
	{
		// @todo not sure about 'together', also check for joinType
		return !isset($relation['on']) &&
		!isset($relation['through']) &&
		!isset($relation['condition']) &&
		!isset($relation['group']) &&
		!isset($relation['join']) &&
		!isset($relation['having']) &&
		!isset($relation['limit']) && // @todo not sure what to do if limit/offset is set
		!isset($relation['offset']) &&
		!isset($relation['scopes']);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteRelation()
	{
		$_transaction = null;
		if ( $this->dbConnection->currentTransaction===null )
			$_transaction=$this->dbConnection->beginTransaction();
		try {
			foreach( $this->relations() as $name => $relation ) {
				switch( $relation[0] ) {
					case CActiveRecord::MANY_MANY:

						if ( !$this->isRelationSupported( $relation ) )
							break;

						foreach( $this->getRelated($name, true) as $record ) {
							$record->delete();
						}
						break;
				}
			}
			if ($_transaction!==null)
				$_transaction->commit();
		} catch(\Exception $e) {
			// roll back internal transaction if one exists
			if ($_transaction!==null)
				$_transaction->rollback();
			// re-throw exception
			throw $e;
		}
	}
}