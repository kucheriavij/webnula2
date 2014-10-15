/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
angular.module('cms.imagesuploader', ['ui.sortable'])
	.directive('imageUploader', ['$uploader', '$parse', '$compile', '$http', function ($uploader, $parse, $compile, $http) {
		return {
			restrict: 'A',
			scope: true,

			link: function ($scope, $element, attrs) {
				$scope.queueCount = 0;
				$scope.errors = [];

				$scope.sortableConfig = {
					items : '.item',
					containment : 'parent',
					placeholder: 'thumbnail placeholder',
					stop: function (e, ui) {
						var next = $(ui.item).next().attr('id');
						var prev = $(ui.item).prev().attr('id');

						var position = 'before';

						if (next === undefined){
							if (prev === undefined) return;

							next = prev;
							position = 'after';
						}

						$http({
							url : $scope.options.url+'/sort/',
							method : 'POST',
							params : { id : $(ui.item).attr('id'), position : position, sibling : next}
						});
					}
				};

				$scope.items = $parse(attrs.items)($scope);
				$scope.options = $parse(attrs.options)($scope);

				$element.browseBtn = $element.find('#browse');
				$element.inputFile = $element.find('input[type="file"]');

				$element.inputFile.hide().on('change', function () {
					var files = this.files;
					$scope.$apply(function () {
						$scope.queueCount += files.length;
						for (var i = 0, file; file = files[i]; i++) {
							$uploader.upload({
								url: $scope.options.url + '/upload/',
								attribute: $scope.options.attribute,
								fieldName: $scope.options.fieldName,
								file: file,
								oncomplete: function (success, data) {
									if (success) {
										$scope.$apply(function () {
											$scope.items.push($.parseJSON(data));
											$scope.queueCount--;
										});
									}
								}
							});
						}
					})
					$(this).val('');
				});

				$scope.remove = function(file) {
					for(var i = 0; i < $scope.items.length; i++) {
						if( $scope.items[i].id === file.id ) {
							$scope.items.splice(i, 1);
							break;
						}
					}
				}

				$element.browseBtn.on('click', function () {
					$element.inputFile.click();
				});
			}
		};
	}]).directive('imageItem', ['$http', '$q', function($http, $q) {
		return {
			restrict : 'A',
			scope : {
				item: '='
			},
			replace: true,

			link: function($scope, $element) {
				var i1 = true, i2 = true;
				$scope.$watch('item.main', function(value) {
					if( i1 ) {
						i1 = false;
						return;
					}
					if (this.canceler !== undefined) {
						this.canceler.resolve();
					}

					this.canceler = $q.defer();

					$http({
						method : 'POST',
						url : $scope.$parent.options.url+'/attribute/',
						params : { id : $scope.item.id, attribute : 'main', value : value ? 1 : 0},
						timeout: this.canceler.promise
					})
				}, true);

				$scope.$watch('item.title', function(value) {
					if( i2 ) {
						i2 = false;
						return;
					}

					if (this.canceler !== undefined) {
						this.canceler.resolve();
					}

					this.canceler = $q.defer();

					$http({
						method : 'POST',
						url : $scope.$parent.options.url+'/attribute/',
						params : { id : $scope.item.id, attribute : 'title', value : value},
						timeout: this.canceler.promise
					})
				}, true);

				$element.on('click', '.delete', function() {
					if( !confirm($scope.$parent.options.confirm) ) {
						return false;
					}
					$http({
						method : 'POST',
						url : this.href
					}).success( function() {
						$scope.$parent.remove($scope.item);
					});
					return false;
				});
			}
		}
	}]);