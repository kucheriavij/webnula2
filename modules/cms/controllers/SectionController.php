<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace cms\controllers;


use cms\components\Controller;
use webnula2\models\Section;
use webnula2\modules\cms\CmsModule;
use webnula2\widgets\booster\TbForm;

/**
 * Class SectionController
 * @package cms\controllers
 */
class SectionController extends Controller
{
	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			'accessControl',
			'postOnly + delete'
		);
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array(
			array( 'allow',
				'actions' => array( 'index', 'update', 'create', 'delete', 'toggle', 'sort', 'tree' ),
				'roles' => array( 'Administrator' )
			),
			array( 'deny',
				'users' => array( '*' )
			)
		);
	}

	/**
	 * @param int $id
	 */
	public function actionIndex( $node = null, $uuid = null )
	{
		$model = new Section();

		if ( \Yii::app()->getRequest()->IsPostRequest && isset( $_POST['next'] ) ) {
			$this->sort();
		}

		$this->render( 'index', array(
			'provider' => $model->search( $node ),
			'model' => $model,
			'parent' => $node,
			'uuid' => $uuid,
			'sortRoute' => isset($node) ? 'array( "id" => $data->id, "href" => $this->controller->createUrl("index", array("uuid" => "'.$uuid.'" ) ) )' : ''
		) );
	}

	/**
	 * @throws \CException
	 */
	private function sort()
	{
		$model = $this->loadModel( $_POST['id'], false );
		$target = $this->loadModel( $_POST['next'], false );
		if ( $model->level > 1 ) {
			switch ( $_POST['position'] ) {
				case 'before':
					$model->moveBefore( $target );
					break;
				case 'after':
					$model->moveAfter( $target );
					break;
			}
		}
	}

	/**
	 * @param $id - section identifier.
	 * @param bool $silent - throw mode.
	 *
	 * @return \CActiveRecord instance or null.
	 * @throws \CException
	 */
	protected function loadModel( $id, $silent = true )
	{
		$model = Section::model()->findByPk( $id );
		if ( !isset( $model ) ) {
			if ( $silent === false ) {
				throw new \CHttpException( 404, CmsModule::t( 'Section #{id} not found.', array( '{id}' => $id ) ) );
			} else {
				$model = new Section();
			}
		}

		return $model;
	}

	/**
	 *
	 */
	public function actionTree()
	{
		if ( isset( $_GET['root'] ) ) {
			$command = \Yii::app()->getDb()->createCommand();
			$nodes = $command->select( 'm1.id, m1.url, m1.parent_id, m1.level, m1.title AS text, m2.id IS NOT NULL AS hasChildren' )
				->from( '{{section}} AS m1' )
				->leftJoin( '{{section}} AS m2', ' m1.id=m2.parent_id' )
				->where( 'm1.parent_id = ?', array( (int)$_GET['root'] ) )
				->group( 'm1.id' )
				->order( 'm1.left_key ASC' );
			$nodes = $command->queryAll();

			foreach ( $nodes as &$node ) {
				$node['text'] = \CHtml::link( $node['text'], array( 'index', 'id' => $node['id'] ) );
				unset( $node['url'] );

				$node['hasChildren'] = (bool)$node['IS NOT NULL AS hasChildren'];
				unset( $node['IS NOT NULL AS hasChildren'] );
			}

			echo \CJavaScript::jsonEncode( $nodes );
		}
	}

	/**
	 * @param $id
	 *
	 * @throws \CException
	 */
	public function actionCreate( $node = null )
	{
		$model = $this->loadModel( @$_POST['id'] );
		$modelName = \CHtml::modelName( $model );
		$model->access = array('Guest');


		$form = new TbForm( $model->forms( $this, $node ) );
		if ( $form->submitted( 'save' ) ) {
			if ( isset( $node ) ) {
				$model->setAttribute( 'url', $node->url . $model->name . '/' );
			} else {
				$model->setAttribute( 'url', '/' . $model->name . '/' );
			}

			if ( $form->validate() ) {
				if ( empty( $model->access ) ) {
					$model->access = array( 'Guest' );
				}

				if ( $node ) {
					$model->appendTo( $node );
				} else {
					$model->saveNode();
				}

				$this->redirect( array( 'section/update', 'uuid' => $model->uuid ) );
			}
		}

		$links = $this->breadcrumbs( $node, false );
		$links[] = CmsModule::t( 'Create section' );

		$this->render( 'form', array(
			'model' => $node,
			'links' => $links,
			'form' => $form
		) );
	}

	/**
	 * @param Section $node
	 */
	public function actionUpdate( Section $node )
	{
		$modelName = \CHtml::modelName( $node );

		$form = new TbForm( $node->forms( $this, $node->parent) );
		if ( $form->submitted( 'save' ) ) {
			$node = $form->getModel();

			if ( $form->validate() ) {
				if ( empty( $node->access ) ) {
					$node->access = array( 'Guest' );
				}

				if ( !empty( $_POST['prevname'] ) && $node->name !== $_POST['prevname'] ) {
					$prevUrl = $node->url;
					$node->url = str_replace( $_POST['prevname'], $node->name, $node->url );
					$node->replaceTo( $prevUrl, $newUrl );
				}
				$node->saveNode();

				$this->redirect( array( 'section/update', 'uuid' => $node->uuid ) );
			}
		}

		$links = $this->breadcrumbs( $node, false );
		$links[] = CmsModule::t( 'Update section #{id}', array( '{id}' => $node->id ) );

		$this->render( 'form', array(
			'model' => $node,
			'links' => $links,
			'form' => $form
		) );
	}

	/**
	 * @param Section $section
	 *
	 * @throws \Exception
	 */
	public function renderModels( Section $section )
	{
		$tabs = array();

		$this->params->mergeWith( array(
			'controller' => $this,
			'node' => $section,
			'uuid' => $section->uuid,
		) );
		foreach ( $section->models as $className ) {
			if ( isset( $this->getModule()->models[$className] ) ) {
				$title = $this->getModule()->models[$className];
				$model = new $className();

				$this->beginClip( $title );

				$this->widget( 'webnula2\widgets\booster\TbButtonGroup', array(
					'buttons' => $this->invokeWith( $model, 'buttons' ),
				) );

				$this->params->add('options', array(
					'id' => strtolower(\CHtml::modelName($model)),
					'template' => '{pager}{items}{pager}',
					'type' => 'striped bordered condensed'
				));
				$this->invokeWith($model, 'render');


				$this->endClip();

				$tabs[] = array(
					'label' => $title,
					'content' => $this->clips[$title]
				);
			}
		}

		$tabs[0]['active'] = true;

		$this->widget( 'webnula2\widgets\booster\TbTabs', array(
			'tabs' => $tabs
		) );
	}

	/**
	 * @param $id
	 *
	 * @throws \CException
	 */
	public function actionDelete( $id )
	{
		$this->loadModel( $id, false )->deleteNode();
	}

	/**
	 * @param $id
	 */
	public function actionToggle( $id, $attribute, $model )
	{
		$modelClass = str_replace( '_', '\\', $model );
		$model = \CActiveRecord::model( $modelClass )->findByPk( $id );
		$value = !$model->$attribute;
		$model->updateByPk( $id, array( $attribute => (int)$value ) );
	}
}