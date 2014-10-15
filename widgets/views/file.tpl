<div file-uploader options="{$options}" items="{$items}">
	<div class="alert alert-warning" style="margin-bottom: 0" ng-show="items.length==0">{$cms->t('Files not loaded.')}</div>
	{literal}
	<div class="list-view files" ui-sortable="sortableConfig" ng-model="items">
		<div class="items clearfix">
			<div class="item" file-item item="item" ng-repeat="item in items" id="{$data->id}">
				<div class="btn btn-sm btn-info sorter" style="float:left;"><span class="glyphicon glyphicon-move"></span></div>
				<div class="col-md-7 form-group" style="float: left">
					<input class="col-md-12 title form-control" ng-model="item.title" type="text">
					<input type="hidden" value="{{ item.id }}" name="{/literal}{$attribute}{literal}">
				</div>
				<a href="{{ item.url }}" class="btn-sm btn btn-success" data-toggle="tooltip" title="{{ item.size|filesize }}"><span class="glyphicon glyphicon-download-alt"></span> {/literal}{$downloadLabel}{literal}</a>
				<a href="{{ options.url }}/delete/id/{{ item.id }}/" class="btn-sm btn delete btn-danger"><span class="glyphicon glyphicon-trash"></span> {/literal}{$deleteLabel}{literal}</a>
			</div>
		</div>
	</div>
	{/literal}
	<div class="row-fluid">
		{CHtml::fileField('file', '', ['multiple' => true])}
		{literal}
			<button class="btn btn-primary btn-sm" type="button" id="browse">
				<div id="process" ng-if="queueCount > 0"><span class="glyphicon glyphicon-refresh infiinite-rotate"></span> Загружается: <span id="count">{{ queueCount }}</span></div>
				<div id="browse" ng-if="!queueCount"><span class="glyphicon glyphicon-upload"></span> Загузить файлы</div>
			</button>
		{/literal}
	</div>
</div>