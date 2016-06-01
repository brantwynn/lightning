(function ($, _, Drupal) {
  "use strict";

  Drupal.behaviors.entityBrowserSelection = {

    onClick: function () {
      $('input[name^="entity_browser_select"]', this).prop('checked', function () {
        return ! this.checked;
      });
      $(this).toggleClass('selected');
    },

    attach: function (context) {
      $('.view [data-selectable]', context).on('click', this.onClick);
    },

    detach: function (context) {
      $('.view [data-selectable]', context).off('click', this.onClick);
    }

  };

  Drupal.behaviors.changeOnKeyUp = {

    onKeyUp: _.debounce(function () { $(this).trigger('change'); }, 600),

    attach: function (context) {
      $('.keyup-change', context).on('keyup', this.onKeyUp);
    },

    detach: function (context) {
      $('.keyup-change', context).off('keyup', this.onKeyUp);
    }

  };

})(jQuery, _, Drupal);
