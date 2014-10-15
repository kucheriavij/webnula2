<?php
/**
 * Navigation component, represent structure of system.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\models;


/**
 * Class Section
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{section}}", indexes={
 *      @Index(name="parent_id", columns={"parent_id"}),
 *      @Index(name="root_id", columns={"root_id"}),
 *      @Index(name="left_right_level", columns={"left_key", "right_key", "level"}),
 *      @Index(name="url", columns={"url"}),
 *      @Index(name="uuid", columns={"uuid"})
 * })
 */
class Section extends Entity
{
	/**
	 * @var bool
	 */
	public $active = false;
	/**
	 * @var bool
	 */
	public $expanded = false;
	/**
	 * @var array
	 */
	public $children = array();
	/**
	 * @Id
	 * @Column(type="integer")
	 */
	private $_id;
	/**
	 * @Column(type="integer")
	 */
	private $_parent_id;
	/**
	 * @Column(type="integer")
	 */
	private $_root_id;
	/**
	 * @Column(type="integer", defaultValue=1)
	 */
	private $_left_key = 1;
	/**
	 * @Column(type="integer", defaultValue=2)
	 */
	private $_right_key = 2;
	/**
	 * @Column(type="integer", defaultValue=1)
	 */
	private $_level = 1;
	/**
	 * @Column(type="boolean", defaultValue=1)
	 */
	private $_publish = 1;
	/**
	 * @Column(type="string", length=512)
	 */
	private $_title;
	/**
	 * @Column(type="string", length=512)
	 */
	private $_name;
	/**
	 * @Column(type="string")
	 */
	private $_route;
	/**
	 * @Column(type="string", length=64)
	 */
	private $_uuid;
	/**
	 * @Column(type="string", length=2000)
	 */
	private $_url;
	/**
	 * @Column(type="string", length=2000, defaultValue="")
	 */
	private $_r_url = '';
	/**
	 * @Column(type="string", length=512, defaultValue="", notnull=true)
	 */
	private $_seo_title = '';
	/**
	 * @Column(type="string", length=512, defaultValue="", notnull=true)
	 */
	private $_seo_keywords = '';
	/**
	 * @Column(type="string", length=512, defaultValue="", notnull=true)
	 */
	private $_seo_description = '';
	/**
	 * @Column(type="boolean", defaultValue=0)
	 */
	private $_seo_sitemap = 0;
	/**
	 * @Column(type="text")
	 */
	private $_models = array();
	/**
	 * @ManyMany(joinTable="{{section_access}}", target="webnula2\models\AuthItem",
	 *      mappedBy = {@JoinColumn(name="section_id", reference="id")},
	 *      inverseBy = {@JoinColumn(name="itemname", reference="name")}
	 * )
	 */
	private $_access;
	/**
	 * @ManyMany(joinTable="{{section_menu}}", target="webnula2\models\Menu",
	 *      mappedBy={@JoinColumn(name="section_id", reference="id")},
	 *      inverseBy={@JoinColumn(name="menu_id", reference="id")}
	 * )
	 */
	private $_menu;

	/**
	 * @return ActiveRecordMetadata
	 */
	public static function metadata()
	{
		return parent::metadata();
	}

	/**
	 * @return bool
	 */
	public function beforeSchemaUpdate()
	{
		return parent::beforeSchemaUpdate();
	}

	/**
	 *
	 */
	public function afterSchemaUpdate()
	{
		if ( null === $model = self::model()->find( "name='home'" ) ) {
			$model = new Section();

			$model->setAttributes( array(
				'parent_id' => 0,
				'title' => \Yii::t( 'webnula2.locale', 'Home page' ),
				'name' => 'home',
				'route' => 'site/index/index',
				'url' => '/',
				'models' => array()
			) );
			$model->saveNode();

			$model->assign( 'Guest' );
		}

		parent::afterSchemaUpdate();
	}

	/**
	 * @param string $className
	 *
	 * @return Section
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @param string $relation
	 */
	public function assign( $itemName )
	{
		if ( \Yii::app()->authManager->getAuthItem( $itemName ) === null )
			throw new \CException( Yii::t( 'yii', 'The item "{name}" does not exist.', array( '{name}' => $itemName ) ) );

		$this->DbConnection->createCommand()
			->insert( '{{section_access}}', array(
				'itemname' => $itemName,
				'section_id' => $this->id,
			) );
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'nestedset' => array(
				'class' => 'webnula2.extensions.NestedSetBehavior',
				'hasManyRoots' => true,
				'rootAttribute' => 'root_id',
				'leftAttribute' => 'left_key',
				'rightAttribute' => 'right_key'
			),
			'ar-relation' => array(
				'class' => 'webnula2.extensions.EActiveRecordRelationBehavior'
			)
		);
	}

	/**
	 * @return array
	 */
	public function relations()
	{
		return array(
			'access' => array( self::MANY_MANY, 'webnula2\models\AuthItem', '{{section_access}}(section_id, itemname)' ),
			'menu' => array(self::MANY_MANY, 'webnula2\models\Menu', '{{section_menu}}(section_id, menu_id)'),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'published' => array( 'condition' => 'publish = 1' ),
		);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'title,name,route', 'required' ),
			array( 'title,name,route', 'length', 'max' => 512 ),
			array( 'uuid', 'length', 'max' => 64 ),
			array( 'r_url, url', 'length', 'max' => 2000 ),
			array( 'name', 'unique', 'criteria' => array( 'condition' => 'parent_id=:id', 'params' => array( 'id' => $this->parent_id ) ) ),
			array( 'seo_title,seo_description,seo_keywords', 'length', 'max' => 512 ),
			array( 'left_key,right_key,level,parent_id,id', 'numerical', 'integerOnly' => true ),
			array( 'seo_sitemap,publish', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 1 ),
			array( 'models,menu,access', 'safe' ),
		);
	}

	/**
	 * @param $itemName
	 *
	 * @return bool
	 */
	public function revoke( $itemName )
	{
		return $this->DbConnection->createCommand()
			->delete( '{{section_access}}', 'itemname=:itemname AND section_id=:id', array(
				':itemname' => $itemName,
				':id' => $this->id
			) ) > 0;
	}

	/**
	 *
	 */
	public function beforeSave()
	{
		if ( parent::beforeSave() ) {
			$this->uuid = sha1( $this->url );
			$this->models = \CJSON::encode( is_array( $this->models ) ? $this->models : array() );

			return true;
		}

		return false;
	}

	/**
	 *
	 */
	public function afterFind()
	{
		$this->models = \CJSON::decode( $this->models );
		parent::afterFind();
	}


	/**
	 * @param $oldUrl
	 */
	public function replaceTo( $oldUrl )
	{
		$db = \Yii::app()->getDb();
		$command = $db->createCommand();
		$transaction = $db->beginTransaction();


		try {
			$descendants = $this->getChildrens();
			foreach ( $descendants as $children ) {
				$url = str_replace( $oldUrl, $this->url, $children->url );
				$children->updateByPk( $children->id, array(
					'url' => $url,
					'uuid' => sha1( $url ),
				) );
			}
			$transaction->commit();
		} catch ( \Exception $e ) {
			$transaction->rollback();
		}
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{section}}';
	}

	/**
	 * @param User|CWebUser $user
	 *
	 * @return boolean
	 */
	public function checkAccess( $user, $params = array() )
	{
		if ( \Yii::app()->getAuthManager()->checkAccess( 'Root', $user->id, $params ) ) {
			return true;
		}

		$result = false;
		$node = $this;
		do {
			foreach ( $node->access as $access ) {
				$result = $result || \Yii::app()->getAuthManager()->checkAccess( $access->name, $user->id, $params );
			}
		} while ( $node = $node->parent );

		return $result;
	}

	/**
	 * CForm constructor.
	 */
	public function forms( $controller, $parent = null )
	{
		if ( $this->isNewRecord )
			$this->parent_id = isset( $parent ) ? $parent->id : 0;

		return array(
			'type' => 'form',
			'title' => $this->isNewRecord ? \Yii::t( 'webnula2.locale', 'Create section' ) : \Yii::t( 'webnula2.locale', 'Update section #{id}', array( '{id}' => $this->id ) ),
			'model' => $this,
			'elements' => array(
				\CHtml::hiddenField( 'prevname', $this->name ),
				'parent_id' => array(
					'type' => 'hidden',
				),
				'<div class="' . ( $this->id == 1 ? '' : 'revert-zone' ) . '">',
				'title' => array(
					'type' => 'text',
					'groupOptions' => array(
						'class' => 'col-md-12 col-xs-12 inline-block-row'
					)
				),
				$controller->widget( 'webnula2\widgets\booster\TbButton', array(
					'context' => 'info',
					'visible' => $this->id != 1,
					'htmlOptions' => array( 'class' => 'translite abs-btn', 'title' => \Yii::t( 'webnula2.locale', 'To translite' ),
						'data-toggle' => "tooltip", 'translite' => '', 'data-target' => '#' . \CHtml::activeId( $this, 'name' ), 'data-rel' => '#' . \CHtml::activeId( $this, 'title' ) ),
					'icon' => 'retweet',
				), true ),
				'</div>',
				'name' => array(
					'type' => 'text',
					'visible' => $this->name !== 'home'
				),
				'publish' => array(
					'type' => 'checkbox',
				),
				'route' => array(
					'type' => 'dropdownlist',
					'items' => $this->route()
				),
				'r_url' => array(
					'type' => 'text'
				),
				'seo_title' => array(
					'type' => 'text'
				),
				'seo_description' => array(
					'type' => 'text'
				),
				'seo_keywords' => array(
					'type' => 'text'
				),
				'models' => array(
					'type' => 'listbox',
					'items' => $controller->getModule()->models,
					'widgetOptions' => array(
						'htmlOptions' => array(
							'multiple' => true
						)
					)
				),
				'access' => array(
					'type' => 'listbox',
					'items' => \CHtml::listData( AuthItem::model()->findAll( 'type=?', array( \CAuthItem::TYPE_ROLE ) ), 'name', 'title' ),
					'widgetOptions' => array(
						'htmlOptions' => array(
							'multiple' => true
						)
					)
				),
				'menu' => array(
					'type' => 'listbox',
					'items' => \CHtml::listData( Menu::model()->findAll(), 'id', 'title' ),
					'widgetOptions' => array(
						'htmlOptions' => array(
							'multiple' => true
						)
					)
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
					'url' => $this->parent_id ? array( 'section/index', 'uuid' => $parent->uuid ) : array( 'section/index' )
				)
			)
		);
	}

	/**
	 * @return array
	 */
	private function route()
	{
		$route = array();
		foreach ( \Yii::app()->getModules() as $id => $config ) {
			if ( !isset( $config['enabled'] ) || $config['enabled'] === true ) {
				$class = new \ReflectionClass( $config['class'] );
				if ( $id === 'cms' && !$class->isSubclassOf( 'webnula2\common\WebModule' ) ) {
					continue;
				}

				if ( !empty( $config['controllerMap'] ) ) {
					foreach ( $config['controllerMap'] as $c_id => $c_config ) {
						$route[$c_config['title']] = array();
						$r = $id . '/' . $c_id;
						if ( !empty( $c_config['actions'] ) && is_array( $c_config['actions'] ) ) {
							$route[$c_config['title']][$r . '/index'] = \Yii::t( 'webnula2.locale', 'Default' );
							foreach ( $c_config['actions'] as $action => $title ) {
								$route[$c_config['title']][$r . '/' . $action] = $title;
							}
						} else {
							$route[$c_config['title']][$r . '/index'] = \Yii::t( 'webnula2.locale', 'Default' );
						}
					}
				}
			}
		}

		return $route;
	}

	/**
	 * Configure CGridView columns.
	 * @return array
	 */
	public function columns( $controller, $node )
	{
		return array(
			array(
				'class' => 'webnula2\widgets\SortableColumn',
				'draggable' => true,
				'visible' => isset($node)
			),
			array(
				'type' => 'raw',
				'header' => \Yii::t( 'webnula2.locale', 'Title' ),
				'value' => 'CHtml::link($data->title, array("section/index", "uuid" => $data->uuid))'
			),
			array(
				'class' => 'webnula2\widgets\ToggleColumn',
				'buttons' => array(
					array(
						'primaryKey' => 'id',
						'toggleAction' => 'toggle',
						'name' => 'publish',
						'value' => '$data->publish',
						'label' => \Yii::t( 'webnula2.locale', 'Publish' ),
						'model' => $this
					)
				),
			),
			array(
				'class' => 'webnula2\widgets\booster\TbButtonColumn',
				'buttons' => array(
					'view' => array(
						'url' => '$data->url'
					),
					'update' => array(
						'url' => 'array("section/update", "uuid" => $data->uuid)'
					),
					'delete' => array(
						'visible' => '$data->id != 1',
						'url' => 'array("section/delete", "id" => $data->id)'
					)
				)
			)
		);
	}

	/**
	 * @param $controller
	 * @param $node
	 *
	 * @return array
	 */
	public function buttons( $controller, $node )
	{
		$id = isset( $node ) ? $node->id : 0;
		$uuid = isset( $node ) ? $node->uuid : '';

		return array(
			array( 'label' => \Yii::t( 'webnula2.locale', 'Create section' ),
				'url' => isset( $node ) ? array( 'section/create', 'uuid' => $uuid ) : array( 'section/create' ),
				'buttonType' => "link",
				'htmlOptions' => array( 'class' => 'btn btn-success' ),
				'context' => 'success',
				'icon' => 'plus'
			),
			array( 'label' => \Yii::t( 'webnula2.locale', 'Update section #{id}', array( '{id}' => $id ) ),
				'url' => array( 'section/update', 'uuid' => $uuid ),
				'visible' => isset( $node ),
				'buttonType' => "link",
				'htmlOptions' => array( 'class' => 'btn btn-primary' ),
				'context' => 'primary',
				'icon' => 'edit'
			)
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'title' => \Yii::t( 'webnula2.locale', 'Title' ),
			'name' => \Yii::t( "webnula2.locale", 'Name' ),
			'route' => \Yii::t( 'webnula2.locale', 'Route' ),
			'publish' => \Yii::t( 'webnula2.locale', 'Publish' ),
			'r_url' => \Yii::t( 'webnula2.locale', 'Redirect url' ),
			'url' => \Yii::t( 'webnula2.locale', 'Url' ),
			'seo_title' => \Yii::t( 'webnula2.locale', 'Seo title' ),
			'seo_description' => \Yii::t( 'webnula2.locale', 'Seo description' ),
			'seo_keywords' => \Yii::t( 'webnula2.locale', 'Seo keywords' ),
			'seo_sitemap' => \Yii::t( 'webnula2.locale', 'Seo sitemap' ),
			'access' => \Yii::t( 'webnula2.locale', 'Access' ),
			'models' => \Yii::t( 'webnula2.locale', 'Page type' ),
			'menu' => \Yii::t( 'webnula2.locale', 'Menu' ),
		);
	}

	/**
	 * @param Section $parent
	 *
	 * @return \CActiveDataProvider
	 */
	public function search( $parent = null )
	{
		$criteria = new \CDbCriteria();

		if ( isset( $parent ) ) {
			$criteria->compare( 'parent_id', $parent->id );
		} else {
			$criteria->compare( 'level', 1 );
			$criteria->compare( 'parent_id', 0 );
		}

		$criteria->order = 'left_key ASC';

		return parent::provider( $criteria, false, $parent );
	}
}