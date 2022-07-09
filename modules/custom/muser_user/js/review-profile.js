/**
 * @file
 * User form functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserReviewProfile = {
    attach: function (context, settings) {
      $('.review-profile', context).once('review-profile--processed').each(function () {

        const $review_profile = $(this);
        const $review_profile__block = $(this).parents('.block-muser-review-profile-block');
        const $cant_close_text = $('<div class="review-profile__timeout"></div>')
        const $closing_wrapper = $('<div class="review-profile__close-wrapper"></div>')

        const $overlay = $('<div class="review-profile-overlay"></div>')
        let $close_button = $(`<button class="review-profile__close button--standard" disabled>${Drupal.t('Close')}</button>`).on('click', function() {
          $review_profile__block.remove();
        })
        $review_profile.append($closing_wrapper);

        $closing_wrapper.append($cant_close_text);
        $closing_wrapper.append($close_button);

        $review_profile__block.append($overlay);

        let timelimit = 5000;
        let timelimit_step = 1000;

        let start_timeout = function() {
          let seconds_left = Math.ceil(timelimit / 1000);
          $cant_close_text[0].innerText = Drupal.formatPlural(
            seconds_left,
            'You may close this message in 1 second.',
            'You may close this message in @sec seconds.',
            {'@sec': seconds_left});

          if (timelimit > 0) {
            setTimeout(function() {
              timelimit -= timelimit_step
              start_timeout();
            }, timelimit_step)
          }
          else {
            $close_button.attr('disabled', false)
            $cant_close_text.remove();
          }
        }
        start_timeout();

      });
    }
  };
})(jQuery, Drupal);
