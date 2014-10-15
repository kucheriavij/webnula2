{extends "cms@layouts.base"}

{block content}
	{$this->widget('webnula2\widgets\booster\TbBreadcrumbs', ['links' => $this->breadcrumbs($parent),'homeLink' => $this->homeLink], true)}
	{if isset($parent)}
		<h3>[{$parent->id}] {$parent->title}</h3>
	{else}
		<h3>{$this->module->t('Sections')}</h3>
	{/if}

	<div class="clear" style="height: 30px"></div>

	{$this->widget('webnula2\widgets\booster\TbButtonGroup', ['buttons' => $model->buttons( $this, $parent )], true)}

	{$this->widget('webnula2\widgets\booster\TbGridView', [
	'id' => 'sections',
	'ajaxUpdate' => 'sections,structure-tree',
	'dataProvider' => $provider,
	'columns' => $model->columns($this,$parent),
	'selectableRows' => 0,
	'rowCssClassExpression' => '$data->checkAccess(Yii::app()->getUser()) ? "allow" : "disallow"',
	'rowHtmlOptionsExpression' => $sortRoute,
	'type' => 'striped bordered condensed',
	'template' => '{items}',
	'afterAjaxUpdate' => 'js: function() {jQuery("#structure-tree").treeview({"url":"/cms/section/tree","animated":true,"persist":"location","prerendered":false});}',
	'responsiveTable' => true
	], true)}

	{if isset($parent) && count($parent->models) > 0}
		<div class="clear" style="height: 30px"></div>
		{$this->renderModels($parent)}
	{/if}
{/block}