<!doctype html>
<html lang="{$Yii->language}" ng-app="cms">
<head>
	<meta charset="UTF-8">
	<title>{$Yii->params->projectName}</title>
</head>
<body>
{$this->widget('webnula2\widgets\booster\TbNavbar', [
'type' => 'inverse',
'brand' => $Yii->params->projectName,
'brandUrl' => $Yii->getRequest()->hostInfo,
'collapse' => true,
'fixed' => 'top',
'fluid' => true,
'items' => [
['class' => 'webnula2\widgets\booster\TbMenu', 'type' => 'navbar', 'items' => $cms->menuItems()],
['class' => 'webnula2\widgets\booster\TbButtonGroup', 'htmlOptions' => ['class' => 'pull-right', 'style'=>'margin: 7.5px 0'], 'buttonType' => 'link', 'buttons' => [
['label' => $Yii->getUser()->get('username'), 'url' => ['user/update', 'id' => $Yii->getUser()->getId()]],
['icon' => 'off', 'url' => ['auth/logout']]
]]
]
], true)}
<div class="container-fluid">
	<div class="row">
		{block 'structure'}
			<div class="col-sm-3 col-md-2 sidebar">
				{$this->widget('webnula2\widgets\TreeView',['id' => 'structure-tree', 'animated' => true, "url" => "/cms/section/tree", "prerendered" => false, "persist" => "location"],true)}
			</div>
		{/block}
		<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
			{block content}
			{/block}
			{if YII_DEBUG}
				<div class="alert alert-warning" style="margin-top: 30px">{$TIME} - {$MEMORY}</div>
			{/if}
		</div>
	</div>
</div>
</body>
</html>