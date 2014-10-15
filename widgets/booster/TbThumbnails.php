<?php
/**
 *## TbThumbnails class file.
 *
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2011-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */


namespace webnula2\widgets\booster;
/**
 *## Bootstrap thumbnails widget.
 *
 * @see http://twitter.github.com/bootstrap/components.html#thumbnails
 *
 * @package booster.widgets.grouping
 */
class TbThumbnails extends TbListView {
	
	/**
	 * Renders the data items for the view.
	 * Each item is corresponding to a single data model instance.
	 * Child classes should override this method to provide the actual item rendering logic.
	 */
	public function renderItems() {
		
		echo \CHtml::openTag($this->itemsTagName, array('class' => $this->itemsCssClass)) . "\n";

		$data = $this->dataProvider->getData();
		echo \CHtml::openTag('div', array('class' => 'row'));
		if (!empty($data)) {
			$owner = $this->getOwner();
			$render = $owner instanceof \CController ? 'renderPartial' : 'render';
			foreach ($data as $i => $item) {
				$data = $this->viewData;
				$data['index'] = $i;
				$data['data'] = $item;
				$data['widget'] = $this;
				$owner->$render($this->itemView, $data);
			}

		} else {
			$this->renderEmptyText();
		}
		echo '</div>';
		echo \CHtml::closeTag($this->itemsTagName);
	}
}
