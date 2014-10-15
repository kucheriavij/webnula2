<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\widgets;

use webnula2\models\Image as ImageModel;
use webnula2\widgets\booster\TbBaseInputWidget;

/**
 * Class Image
 * @package webnula2\widgets
 */
class Image extends TbBaseInputWidget {
	/**
	 * @var
	 */
	public $form;

	/**
	 * @var
	 */
	public $primaryKey;

	/**
	 * @var
	 */
	public $options;

	/**
	 *
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @throws \CException
	 */
	public function run()
	{
		$model = new ImageModel();
		$items = array();
		foreach( $this->model->getRelated($this->attribute, true, array('order' => 'sort ASC')) as $record ) {
			$items[] = array(
				'url' => $record->getUrl('t260x180'),
				'title' => $record->title,
				'main' => $record->main ? true : false,
				'id' => (int)$record->id
			);
		}
		if( !isset($this->options['url']) ) {
			$this->options['url'] = \CHtml::normalizeUrl(array('/cms/image'));
		}

		if( !isset($this->options['fieldName']) ) {
			$this->options['fieldName'] = 'fileData';
		}

		$this->options['id'] = $this->getId();
		if( empty($this->options['confirm']) ) {
			$this->options['confirm'] = \Yii::t('zii','Are you sure you want to delete this item?');
		}


		$this->render('image', array(
			'id' => $this->getId(),
			'form' => $this->form,
			'attribute' => \CHtml::activeName($this->model, "{$this->attribute}[]"),
			'title' => \CHtml::activeName($model, 'title'),
			'main' => \CHtml::activeName($model, 'main'),
			'sortLabel' => \Yii::t('webnula2.locale', 'Drag for sort.'),
			'deleteLabel' => \Yii::t('webnula2.locale', 'Delete'),
			'mainLabel' => \Yii::t('webnula2.locale', 'Main'),
			'items' => addcslashes(\CJavaScript::encode($items), '"'),
			'options' => addcslashes(\CJavaScript::encode($this->options), '"'),
		));
	}
}