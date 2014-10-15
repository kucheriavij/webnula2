/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
angular.module('cms.translite', []).directive('translite', function() {
   return {
       restrict : 'A',

       link : function($scope, $element) {
            $element.on('click', function() {
                var $target = $($(this).data('target'));
                var $rel = $($(this).data('rel'));
                $target.val($rel.val().toLowerCase().translit().replace(/(\W+)/gi, '-'));
            })
       }
   }
});