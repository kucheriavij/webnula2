<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\widgets;

use webnula2\widgets\booster\TbDataColumn;

/**
 * Class ToggleColumn
 * @package webnula2\widgets
 */
class ToggleColumn extends TbDataColumn
{
	/**
	 * @var array
	 */
	public $htmlOptions = array('class' => 'toggle-column');

	/**
	 * @var array
	 */
	public $headerHtmlOptions = array('style' => 'width: 130px');

	/**
	 * @var string
	 */
	public $checkedIcon = 'glyphicon glyphicon-ok-circle';

	/**
	 * @var string
	 */
	public $uncheckedIcon = 'glyphicon glyphicon-remove-circle';

	/**
	 * @var string
	 */
	public $toggleAction = 'toggle';

	/**
	 * @var array
	 */
	public $buttons = array();

	/**
	 * @var
	 */
	public $model;

	/**
	 *
	 */
	public function init()
	{
		$this->registerClientScript();
	}

	/**
	 * Registers the client scripts for the button column.
	 */
	protected function registerClientScript()
	{
		if (\Yii::app()->request->enableCsrfValidation) {
			$csrfTokenName = \Yii::app()->request->csrfTokenName;
			$csrfToken = \Yii::app()->request->csrfToken;
			$csrf = "\n\t\tdata:{ '$csrfTokenName':'$csrfToken' },";
		} else {
			$csrf = '';
		}

		$function = \CJavaScript::encode("js:
function() {
	var th=this;
	$.fn.yiiGridView.update('{$this->grid->id}', {
		type:'POST',
		url:$(this).attr('href'),{$csrf}
		success:function(data) {
			$.fn.yiiGridView.update('{$this->grid->id}');
		}
	});
	return false;
}");
		if(is_string($this->model)) {
			$modelId = $this->model;
		} else {
			$modelId = get_class($this->model);
		}
		\Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $modelId, "$(document).on('click','#{$this->grid->id} .toggle-column a',$function);");
	}

	/**
	 * Renders the data cell content.
	 * This method renders the view, update and toggle buttons in the data cell.
	 *
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row, $data)
	{
		foreach($this->buttons as $button) {
			$checked = $this->evaluateExpression($button['value'], array('data' => $data, 'row' => $row));

			if( isset($button['visible']) ) {
				if( !$this->evaluateExpression($button['visible'], array('data' => $data, 'row' => $row)) ) {
					continue;
				}
			}

			if(is_string($button['model'])) {
				$modelId = $button['model'];
			} else {
				$modelId = get_class($button['model']);
			}
			$icon = ($checked ? $this->checkedIcon : $this->uncheckedIcon);
			$url = $this->grid->controller->createUrl($this->toggleAction, array('id' => $data->{$button['primaryKey']}, 'attribute' => $button['name'], 'model' => strtr($modelId, array('\\' => '_'))));

			if( !isset($button['options']) ) {
				$button['options'] = array(
					'style' => 'display:block;white-space: nowrap;margin-top: 5px;',
				);
			}
			unset($button['name'],$button['value']);
			echo \CHtml::link('<i class="' . $icon . '"></i>&nbsp;'.$button['label'], $url, $button['options']);
		}
	}
}