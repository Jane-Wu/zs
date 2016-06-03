/**
 * @file
 * Bootstrap Tooltips.
 */

var Drupal = Drupal || {};

(function ($, Drupal) {
  "use strict";
  /**
   */
  Drupal.behaviors.autoFooter = {
    attach: function (context) {
     var bodyMinHeight = $(window).height() - 160;
     $("div.main-container").css({"min-height": bodyMinHeight + "px"});
    },
  };

})(window.jQuery, window.Drupal);
