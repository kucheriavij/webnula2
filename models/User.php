<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;
use webnula2\components\Kernel;


/**
 * Class User
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{user}}", indexes={
 *      @Index(name="username", columns={"username", "usermail"}),
 *      @Index(name="useractive", columns={"useractive"}),
 *      @Index(name="userhash", columns={"userhash"})
 * })
 */
class User extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 */
	private $_id;

	/**
	 * @Column(type="string", length=50)
	 */
	private $_username;

	/**
	 * @Column(type="string", length=60)
	 */
	private $_password;

	/**
	 * @Column(type="string", length=50)
	 */
	private $_usermail;

	/**
	 * @Column(type="boolean", defaultValue=1)
	 */
	private $_useractive;

	/**
	 * @Column(type="datetime")
	 */
	private $_userlastvisit;

	/**
	 * @Column(type="datetime")
	 */
	private $_userregtime;

	/**
	 * @Column(type="string")
	 */
	private $_userhash;

	/**
	 * @Column(type="text")
	 */
	private $_models;

	/**
	 * @var array
	 */
	private $_groupNames;

	/**
	 *
	 */
	public function afterSchemaUpdate()
	{
		if ( null === $model = self::model()->findByPk( 1 ) ) {
			$model = new User();
			$model->setScenario('initial');
			$model->setAttributes( array(
				'username' => 'admin',
				'password' => '$2a$13$rnXs3c6ChlINmA0q.1t1pu/ho4hZCIUBzMRdhouUWShbxgXzZ6EK.',
				'usermail' => 'admin@' . \Yii::app()->params->projectHost,
				'useractive' => 1,
				'userregtime' => date( 'Y-m-d H:i:s' ),
				'userlastvisit' => date( 'Y-m-d H:i:s' ),
				'groups' => array('Root')
			) );
			$model->save();
		}
	}

	/**
	 * @param string $className
	 *
	 * @return \CActiveRecord
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{user}}';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'username,usermail', 'length', 'max' => 50 ),
			array( 'password', 'length', 'max' => 60 ),
			array( 'userhash', 'length', 'max' => 255 ),
			array( 'id', 'numerical', 'integerOnly' => true ),
			array( 'userlastvisit,userregtime,groups', 'safe' ),
			array( 'useractive', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 1 ),
			array( 'username,usermail', 'required' ),
			array( 'password', 'required', 'on' => 'register' ),
			array( 'usermail', 'email' ),
			array( 'usermail,username', 'unique' ),
			array( 'models,groups', 'safe' ),
		);
	}

	/**
	 * @return array
	 */
	public function relations()
	{
		$relations = array();
		if( isset(Kernel::get()->relations['user']) ) {
			$relations = (array)Kernel::get()->relations['user'];
			//settype($relations, 'array');
		}

		return $relations + array(
			'groups' => array( self::MANY_MANY, 'webnula2\models\AuthItem', '{{authassignment}}(userid, itemname)' )
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		$behaviors = array();
		if( isset(Kernel::get()->relations['user']) ) {
			$behaviors = (array)Kernel::get()->relations['user'];
			//settype($behaviors, 'array');
		}

		return $behaviors+ array(
			'ar-relation' => 'webnula2.extensions.EActiveRecordRelationBehavior',
		);
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if ( parent::beforeSave() ) {
			$this->models = \CJSON::encode( $this->models );

			switch ( $this->scenario ) {
				case 'register':
				case 'insert':
					$this->password = $this->hashPassword( $this->password );
					$this->userregtime = date( 'Y-m-d H:i:s' );
					break;
				case 'profile':
				case 'update':
					if ( !empty( $this->password ) ) {
						$this->password = $this->hashPassword( $this->password );
					}
					break;
			}

			return true;
		}

		return false;
	}

	/**
	 * Generates the password hash.
	 *
	 * @param string password
	 *
	 * @return string hash
	 */
	public function hashPassword( $password )
	{
		return \CPasswordHelper::hashPassword( $password );
	}

	/**
	 *
	 */
	public function afterFind()
	{
		parent::afterFind();
		$this->models = \CJSON::decode( $this->models );
	}

	/**
	 * @param $controller
	 *
	 * @return array
	 */
	public function forms( $controller )
	{
		return array(
			'title' => $this->isNewRecord ? \Yii::t( 'webnula2.locale', 'Create user' ) : \Yii::t( 'webnula2.locale', 'Update user #{id}', array( '{id}' => $this->id ) ),
			'model' => $this,
			'type' => 'form',
			'elements' => array(
				'username' => array(
					'type' => 'text'
				),
				'usermail' => array(
					'type' => 'text'
				),
				'password' => array(
					'type' => 'password'
				),
				'useractive' => array(
					'type' => 'checkbox'
				),
				'groups' => array(
					'type' => 'listbox',
					'items' => \CHtml::listData( AuthItem::model()->findAll( 'type=?', array( \CAuthItem::TYPE_ROLE ) ), 'name', 'title' ),
					'widgetOptions' => array(
						'htmlOptions' => array( 'multiple' => true )
					)
				),
				'models' => array(
					'type' => 'listbox',
					'widgetOptions' => array()
				)
			),
			'buttons' => array(
				'save' => array(
					'buttonType' => 'submit',
					'context' => 'primary',
					'label' => \Yii::t( 'webnula2.locale', 'Save' ),
				),
				'cancel' => array(
					'buttonType' => 'link',
					'context' => 'default',
					'label' => \Yii::t( 'webnula2.locale', 'Cancel' ),
					'url' => array( 'index' )
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'username' => \Yii::t( 'webnula2.locale', 'Username' ),
			'usermail' => \Yii::t( 'webnula2.locale', 'Usermail' ),
			'password' => \Yii::t( 'webnula2.locale', 'Password' ),
			'useractive' => \Yii::t( 'webnula2.locale', 'Status' ),
			'userhash' => \Yii::t( 'webnula2.locale', 'User hash' ),
			'groups' => \Yii::t( 'webnula2.locale', 'Groups' ),
			'models' => \Yii::t( 'webnula2.locale', 'Models' ),
			'useregtime' => \Yii::t( 'webnula2.locale', 'Register time' ),
			'userlastvisit' => \Yii::t( 'webnula2.locale', 'Last visit' ),
		);
	}

	/**
	 * @param $controller
	 */
	public function buttons( $controller )
	{
		return array(
			array( 'label' => \Yii::t( 'webnula2.locale', 'Create user' ),
				'url' => array( 'user/create' ),
				'buttonType' => "link",
				'htmlOptions' => array( 'class' => 'btn btn-success' ),
				'context' => 'success',
				'icon' => 'plus'
			)
		);
	}

	/**
	 * @param $controller
	 *
	 * @return array
	 */
	public function columns( $controller )
	{
		return array(
			array(
				'header' => '#',
				'value' => '$data->id',
			),
			array(
				'header' => \Yii::t( 'webnula2.locale', 'Username' ),
				'value' => '$data->username'
			),
			array(
				'class' => 'webnula2\widgets\booster\TbButtonColumn',
				'template' => '{update}{delete}',
			)
		);
	}

	/**
	 *
	 */
	public function search()
	{
		$criteria = new \CDbCriteria();

		return parent::provider( $criteria, 20, array( 'index' ) );
	}

	/**
	 *
	 */
	public function getGroupsName()
	{
		if ( $this->_groupNames === null ) {

		}

		return $this->_groupNames;
	}

	/**
	 * Checks if the given password is correct.
	 *
	 * @param string the password to be validated
	 *
	 * @return boolean whether the password is valid
	 */
	public function validatePassword( $password )
	{
		return \CPasswordHelper::verifyPassword( $password, $this->password );
	}
}