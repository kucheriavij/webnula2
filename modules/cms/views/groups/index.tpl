{extends "cms@layouts.base"}

{block content}
	<h3>{$this->module->t('Groups')}</h3>
	<div class="clear" style="height: 30px"></div>
	{$this->widget('webnula2\widgets\booster\TbButtonGroup', ['buttons' => $model->buttons($this)], true)}

	{$this->widget('webnula2\widgets\booster\TbGridView', [
		'id' => 'groups',
		'dataProvider' => $provider,
		'columns' => $model->columns(),
		'selectableRows' => 1,
		'type' => 'striped bordered condensed',
		'template' => '{items}',
		'responsiveTable' => true
	], true)}
{/block}