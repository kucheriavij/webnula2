<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\widgets;

use webnula2\models\Section;

class TreeView extends \CTreeView {
	/**
	 * Initialize tree widget.
	 */
	public function init()
	{
		$assetsUrl = (YII_DEBUG ?
			\Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets/treeview', false, -1, true) :
			\Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets/treeview')
		);
		$this->cssFile = $assetsUrl.'/treeview.css';

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$id=$this->htmlOptions['id']=$this->getId();
		if($this->url!==null)
			$this->url=\CHtml::normalizeUrl($this->url);
		$cs=\Yii::app()->getClientScript();
		$cs->registerCoreScript('treeview');
		$options=$this->getClientOptions();
		$options=$options===array()?'{}' : \CJavaScript::encode($options);
		$cs->registerScript('Yii.CTreeView#'.$id,"jQuery(\"#{$id}\").treeview($options);");
		$cs->registerCssFile($this->cssFile);


		$this->data = self::createTree( $this->records() );
		echo \CHtml::tag('ul',$this->htmlOptions,false,false)."\n";
		echo self::saveDataAsHtml($this->data);
	}

	/**
	 * @return loaded records.
	 */
	protected function records()
	{
		$records = array();

		$user = \Yii::app()->getComponent('user');
		if( isset($this->controller->params['node']) ) {
			$descendant = $this->controller->params['node'];

			// ok! we can build preload structure.
			if( isset($descendant) ) {
				$nodes = array_merge($descendant->ancestors()->findAll(), array($descendant));

				foreach( $nodes as $node ) {
					$childrens = $node->children()->findAll();
					$records[$node->id] = array(
						'text' => \CHtml::link($node->title, array('/cms/section/index', 'uuid' => $node->uuid), array('class' => 'selected')),
						'parent_id' => $node->parent_id,
						'id' => $node->id,
						'access' => (int)$node->checkAccess($user),
						'hasChildren' => count($childrens) > 0
					);

					foreach($childrens  as $children ) {
						$records[$children->id] = array(
							'text' => \CHtml::link($children->title, array('/cms/section/index', 'uuid' => $children->uuid)),
							'parent_id' => $children->parent_id,
							'id' => $children->id,
							'access' => (int)$children->checkAccess( $user ),
							'hasChildren' => $children->children()->count() > 0
						);
					}
				}
			}
		} else {
			$nodes = Section::model()->findAll(array('condition' => 'level=1','order' => 'id ASC'));

			foreach( $nodes as $node ) {
				$childrens = $node->children()->findAll();
				$records[$node->id] = array(
					'text' => \CHtml::link($node->title, array('/cms/section/index', 'uuid' => $node->uuid), array('class' => 'selected')),
					'parent_id' => $node->parent_id,
					'id' => $node->id,
					'access' => (int)$node->checkAccess($user),
					'hasChildren' => count($childrens) > 0
				);
			}

			$node = reset($nodes);
			if( $node ) {
				$childrens = $node->children()->findAll();
				$records[$node->id] = array(
					'text' => \CHtml::link( $node->title, array( '/cms/section/index', 'uuid' => $node->uuid ), array( 'class' => 'selected' ) ),
					'parent_id' => $node->parent_id,
					'id' => $node->id,
					'access' => (int)$node->checkAccess( $user ),
					'hasChildren' => count( $childrens ) > 0
				);

				foreach ( $childrens as $children ) {
					$records[$children->id] = array(
						'text' => \CHtml::link( $children->title, array( '/cms/section/index', 'uuid' => $children->uuid ) ),
						'parent_id' => $children->parent_id,
						'id' => $children->id,
						'access' => (int)$children->checkAccess( $user ),
						'hasChildren' => $children->children()->count() > 0
					);
				}
			}
		}
		return array_values($records);
	}

	/**
	 * Create and return tree of the records.
	 * @param object $collection - flat list of the collections.
	 * @return tree structure.
	 */
	public static function createTree($collection){
		$children = array();
		$ids = array();
		foreach ($collection as $i=>$r) {
			$row =& $collection[$i];
			$id = $row['id'];
			$pid = $row['parent_id'];
			if (!isset($children[$pid])) $children[$pid] = array();
			$children[$pid][$id] =& $row;
			if (!isset($children[$id])) $children[$id] = array();
			$row['children'] =& $children[$id];
			$row['expanded'] = true;
			$ids[$row['id']] = true;
		}
		$forest = array();
		foreach ($collection as $i=>$r) {
			$row =& $collection[$i];

			if (!isset($ids[$row['parent_id']])) $forest[$row['id']] =& $row;
			if ($row['hasChildren'] == 0 ) {
				unset($row['children'], $row['expanded']);
			}
		}
		return $forest;
	}

	/**
	 * Generates tree view nodes in HTML from the data array.
	 * @param array $data the data for the tree view (see {@link data} for possible data structure).
	 * @return string the generated HTML for the tree view
	 */
	public static function saveDataAsHtml($data)
	{
		$html='';
		if(is_array($data))
		{
			foreach($data as $node)
			{
				if( !isset($node['text']) )
					continue;

				if(isset($node['expanded']) && !empty($node['children']))
					$css=$node['expanded'] ? 'open' : 'closed';
				else
					$css='';

				if(isset($node['hasChildren']) && $node['hasChildren'] && empty($node['children']))
				{
					if($css!=='')
						$css.=' ';
					$css.='hasChildren';
				}

				if( $node['access'] === 0 ) {
					$css .= ' denied';
				}

				$options=isset($node['htmlOptions']) ? $node['htmlOptions'] : array();
				if($css!=='')
				{
					if(isset($options['class']))
						$options['class'].=' '.$css;
					else
						$options['class']=$css;
				}

				if(isset($node['id']))
					$options['id']=$node['id'];

				$html.=\CHtml::tag('li',$options,$node['text'],false);
				if($node['hasChildren'])
				{
					$html.="\n<ul>\n";
					if( !empty($node['children']) ) {
						$html.=self::saveDataAsHtml($node['children']);
					} else {
						$html .= '<ul><li><span class="placeholder">&nbsp;</span></li></ul>';
					}
					$html.="</ul>\n";
				}
				$html.=\CHtml::closeTag('li')."\n";
			}
		}
		return $html;
	}
}