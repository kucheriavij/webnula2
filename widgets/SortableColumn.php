<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\widgets;

use webnula2\components\Booster;
use webnula2\widgets\booster\TbButtonColumn;

/**
 * Class SortableColumn
 * @package webnula2\widgets
 */
class SortableColumn extends \CGridColumn {
	/**
	 * @var array
	 */
	public $headerHtmlOptions = array('class' => 'sortable-header-column', 'style' => 'width: 30px');
	/**
	 * @var array
	 */
	public $htmlOptions = array('class' => 'sortable-column', 'style' => 'text-align: center');

	/**
	 * @var bool
	 */
	public $draggable = false;

	/**
	 * @var array
	 */
	public $buttons = array();

	/**
	 * @var string
	 */
	public $template='';

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		if( $this->visible ) {
			if ( $this->draggable ) {
				if ( \Yii::app()->request->enableCsrfValidation ) {
					$csrfTokenName = \Yii::app()->request->csrfTokenName;
					$csrfToken = \Yii::app()->request->csrfToken;
					$csrf = "{'$csrfTokenName':'$csrfToken' }";
				} else
					$csrf = '{}';

				\Yii::app()->getClientScript()->registerScriptFile( $this->grid->baseScriptUrl . '/jquery.yiigridview.js', \CClientScript::POS_END );
				Booster::getBooster()->registerAssetJs( 'jquery.sortable.gridview.js' );

				$this->buttons = array(
					'drag' => array(
						'icon' => 'resize-vertical',
						'options' => array( 'class' => 'handler', 'title' => '' )
					)
				);
				$this->template = '{drag}';
				\Yii::app()->getClientScript()->registerScript( __CLASS__ . '#' . $this->grid->controller->id, "$.fn.yiiGridView.sortable('{$this->grid->id}', null, $csrf);" );
			} else {
				$function = \CJavaScript::encode( "js:
	function() {
		var tr = $(this).parents('tr');
		var myid = tr.attr('id');
		alert($(this).attr('dir'))
		$.fn.yiiGridView.update('{$this->grid->id}', {
			type:'POST',
			url:tr.attr('href'),
			data : { id : myid, position : $(this).attr('dir') },
			success:function(data) {
				$.fn.yiiGridView.update('{$this->grid->id}');
			}
		});
		return false;
	}
	" );
				\Yii::app()->getClientScript()->registerScript( __CLASS__ . '#' . $this->grid->id, "jQuery(document).on('click', '#{$this->grid->id} .sortable-column a', {$function})" );
			}
		}
	}

	/**
	 * Renders the data cell content.
	 * This method renders the view, update and delete buttons in the data cell.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row,$data)
	{
		if( $this->visible ) {
			$tr = array();
			ob_start();
			foreach ( $this->buttons as $id => $button ) {
				$this->renderButton( $id, $button, $row, $data );
				$tr['{' . $id . '}'] = ob_get_contents();
				ob_clean();
			}
			ob_end_clean();
			echo strtr( $this->template, $tr );
		}
	}

	/**
	 * @param $id
	 * @param $button
	 * @param $row
	 * @param $data
	 */
	protected function renderButton($id, $button, $row, $data) {
		if (isset($button['visible']) && !$this->evaluateExpression(
				$button['visible'],
				array('row' => $row, 'data' => $data)
			)
		) {
			return;
		}

		$label = isset($button['label']) ? $button['label'] : $id;
		$url = isset($button['url']) ? $this->evaluateExpression($button['url'], array('data' => $data, 'row' => $row))
			: '#';
		$options = isset($button['options']) ? $button['options'] : array();

		if (!isset($options['title'])) {
			$options['title'] = $label;
		}

		$options['dir'] = $button['dir'];

		if (isset($button['icon']) && $button['icon']) {
			if (strpos($button['icon'], 'icon') === false && strpos($button['icon'], 'fa') === false) {
				$button['icon'] = 'glyphicon glyphicon-' . implode('glyphicon-', explode(' ', $button['icon']));
			}

			echo \CHtml::link('<i class="' . $button['icon'] . '"></i>', $url, $options);
		} else if (isset($button['imageUrl']) && is_string($button['imageUrl'])) {
			echo \CHtml::link(\CHtml::image($button['imageUrl'], $label), $url, $options);
		} else {
			echo \CHtml::link($label, $url, $options);
		}
	}
}