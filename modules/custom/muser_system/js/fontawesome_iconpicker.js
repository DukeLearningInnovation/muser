/**
 * @file
 * Description.
 */

(function ($, Drupal) {

  Drupal.behaviors.fontawesomeIconpicker = {
    attach: function (context, settings) {
      $(context).find('.fontawesome-iconpicker-element').once('jsFontawesomeIconpicker').each(function () {
        let $this = $(this);
        $this.parent().append($('<span class="input-group-addon__wrapper"><span class="input-group-addon"></span><i class="edit-icon-button fas fa-angle-down"></i></span>'));
        $this.iconpicker({
          hideOnSelect: true,
          inputSearch: false,
          placement: 'bottomLeft',
        });
        $this.on('iconpickerShown', function(e) {
          let parent = e.iconpickerInstance.popover;
          parent.find('.iconpicker-search').focus();
        })
      });
    }
  }

})(jQuery, Drupal);
