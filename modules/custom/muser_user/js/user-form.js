/**
 * @file
 * User form functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserUserForm = {
    attach: function (context, settings) {
      $('.user-form', context).once('user-form--processed').each(function () {

        $('.form-item-current-pass').hide();
        $('.form-item-pass').hide();

        $('#edit-name, #edit-mail').on('paste', function () {
          $('.form-item-current-pass').show();
        });
        $('#edit-name, #edit-mail').keyup(function () {
          $('.form-item-current-pass').show();
        });
        $('.change-pass').on('click', function () {
          $('.form-item-current-pass').show().find('input').focus();
          $('.form-item-pass').show();
          $(this).hide();
          return false;
        });

      });
    }
  };
})(jQuery, Drupal);
