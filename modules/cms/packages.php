<?php
return array(
	'style.css' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'css' => array('css/style.css'),
	),

	'angular' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/angular.min.js'),
	),

	'cms.app' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/app.js'),
	),

	'cms.fileuploader' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/modules/cms.fileuploader.js'),
	),

	'cms.imageuploader' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/modules/cms.imageuploader.js'),
	),

	'ui.sortable' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/modules/ui.sortable.js'),
	),

	'cms.translite' => array(
		'baseUrl' => $this->getAssetsUrl(),
		'js' => array('js/modules/cms.translite.js'),
	),
);