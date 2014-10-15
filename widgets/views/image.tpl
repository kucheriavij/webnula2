<div image-uploader options="{$options}" items="{$items}">
	<div class="alert alert-warning" style="margin-bottom: 0" ng-show="items.length==0">{$cms->t('Images not loaded.')}</div>
	<div class="list-view">
		<div class="items row" ui-sortable="sortableConfig" ng-model="items">
			{literal}
			<div class="thumbnail item" image-item item="item" ng-repeat="item in items" id="{{ item.id }}">
				<div class="ui-sort-handler btn btn-info" data-toggle="tooltip" title="{/literal}{$sortLabel}{literal}"><span class="glyphicon glyphicon-move"></span></div>
				<img ng-if="!!item.url" ng-src="{{ item.url }}" alt="Chrysanthemum">
				<div class="caption">
					<p class="clearfix form-group">
						<input class="col-md-12 title form-control" ng-model="item.title" type="text">
						<input type="hidden" value="{{ item.id }}" name="{/literal}{$attribute}{literal}">
					</p>
					<p class="clearfix btnbar">
						<a class="delete btn btn-danger btn-sm" href="{{ options.url }}/delete/id/{{ item.id }}"><span class="glyphicon glyphicon-trash"></span> {/literal}{$deleteLabel}{literal}</a>
						<label><input ng-model="item.main" type="checkbox">{/literal}{$mainLabel}{literal}</label>
					</p>
				</div>
			</div>
			{/literal}
		</div>
	</div>
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