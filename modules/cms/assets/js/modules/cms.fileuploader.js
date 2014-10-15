/**
* @author Martyushev Dmitriy (dangozero@gmail.com)
* @copyright dangozero at gmail dot com
* @license LICENSE
*/
angular.module('cms.fileuploader',[])
    .directive('fileUploader', ['$uploader', '$parse', '$compile', '$http', function($uploader, $parse, $compile, $http) {
        return {
            restrict : 'A',
            scope : true,

            link : function($scope, $element, attrs) {
                $scope.queueCount = 0;
                $scope.errors = [];
                $scope.items = [];

                $scope.sortableConfig = {
                    items : '.item',
	                containment : 'parent',
	                placeholder: 'item placeholder',
                    stop : function(e, ui) {
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

                $element.browseBtn = $element.find('#browse');
                $element.inputFile = $element.find('input[type="file"]');
                $scope.options = $parse(attrs.options)($scope);

                $element.inputFile.hide().on('change', function() {
                    var files = this.files;
                    $scope.$apply(function() {
                        $scope.queueCount += files.length;
                        for(var i = 0, file; file = files[i]; i++) {
                            $uploader.upload({
                                url: $scope.options.url + '/upload/',
                                attribute : $scope.options.attribute,
                                fieldName: $scope.options.fieldName,
                                file : file,
                                oncomplete: function (success, data) {
                                    if( success ) {
                                        $scope.$apply(function () {
	                                        $scope.items.push($.parseJSON(data));
                                            $scope.queueCount--;
                                        });
	                                    $('[data-toggle="tooltip"').tooltip();
                                    }
                                }
                            });
                        }
                    });

	                $(this).val('');
                })

                $element.browseBtn.on('click', function() {
                    $element.inputFile.click();
                });

	            $scope.remove = function(file) {
		            for(var i = 0; i < $scope.items.length; i++) {
			            if( $scope.items[i].id === file.id ) {
				            $scope.items.splice(i, 1);
				            break;
			            }
		            }
	            }
            }
        };
    }]).directive('fileItem', ['$http','$q', function($http, $q) {
		return {
			restrict : 'A',

			scope : {
				item : '='
			},

			link : function($scope, $element) {
				var i2 = true;

				$scope.$watch('item.title', function() {
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
				});

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