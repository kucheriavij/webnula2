<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\widgets;

use webnula2\models\File as FileModel;
use webnula2\widgets\booster\TbBaseInputWidget;

/**
 * Class File
 * @package webnula2\widgets
 */
class File extends TbBaseInputWidget {
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
		$model = new FileModel();
		$items = array();
		foreach( $this->model->getRelated($this->attribute, true, array('order' => 'sort ASC')) as $record ) {
			$items[] = array(
				'url' => $record->file['url'],
				'title' => $record->title,
				'id' => (int)$record->id,
				'size' => $record->file['size'],
			);
		}
		if( !isset($this->options['url']) ) {
			$this->options['url'] = \CHtml::normalizeUrl(array('/cms/file'));
		}

		if( !isset($this->options['fieldName']) ) {
			$this->options['fieldName'] = 'fileData';
		}

		$this->options['id'] = $this->getId();
		if( empty($this->options['confirm']) ) {
			$this->options['confirm'] = \Yii::t('zii','Are you sure you want to delete this item?');
		}


		$this->render('file', array(
			'id' => $this->getId(),
			'form' => $this->form,
			'attribute' => \CHtml::activeName($this->model, "{$this->attribute}[]"),
			'title' => \CHtml::activeName($model, 'title'),
			'sortLabel' => \Yii::t('webnula2.locale', 'Drag for sort.'),
			'deleteLabel' => \Yii::t('webnula2.locale', 'Delete'),
			'downloadLabel' => \Yii::t('webnula2.locale', 'Download'),
			'items' => addcslashes(\CJavaScript::encode($items), '"'),
			'options' => addcslashes(\CJavaScript::encode($this->options), '"'),
		));
	}
}