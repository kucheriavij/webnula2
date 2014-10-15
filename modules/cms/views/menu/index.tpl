{extends "cms@layouts.base"}

{block content}
	<h3>{$cms->t('Menu')}</h3>

	<div class="clear" style="height: 30px"></div>

	{$this->widget('webnula2\widgets\booster\TbButtonGroup', ['buttons' => $model->buttons( )], true)}

	{$this->widget('webnula2\widgets\booster\TbGridView', [
	'id' => 'menu',
	'dataProvider' => $model->search(),
	'columns' => $model->columns(),
	'selectableRows' => 0,
	'type' => 'striped bordered condensed',
	'template' => '{items}',
	'responsiveTable' => true
	], true)}
{/block}