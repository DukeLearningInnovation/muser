/**
 * @file
 * View filter functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserTokenPreview = {
    attach: function (context, settings) {

      let previewWindow = '<div id="token-preview-window"><input type="button" class="close-preview-window" value="X"/><div class="content"></div></div>';
      if ($('#token-preview-window').length === 0) {
        $('.token-preview-enabled').prepend(previewWindow);
        $('#token-preview-window').hide();
        $('#token-preview-window .close-preview-window').on('click', function () {
          $('#token-preview-window').hide();
        });
      }

      $('.token-preview-button').once('token-preview-processed').each(function () {

        $(this).on('click', function () {

          let field_name = $(this).attr('data-field-name');
          let url = '/muser/config-item-with-tokens/' + field_name;
          let value = $('[name="' + field_name + '"]').val();
          $.ajax({
            url : url,
            data:{'value':value, 'format':''},
            type: 'POST',
            success: function (data) {
              let markup = '<h3 class="token-preview__title">Token replacement preview</h3><div class="token-text__body">' + data + '</div>';
              $('#token-preview-window').show().find('.content').html(markup);
            }
          });
          return false;

        });

      });

    }
  };
})(jQuery, Drupal);
