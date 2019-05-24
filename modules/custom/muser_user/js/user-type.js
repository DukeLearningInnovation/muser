/**
 * @file
 * User form functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserUserType = {
    attach: function (context, settings) {
      $('.user-form', context).once('user-type--processed').each(function () {
        let $change_user_type = $('.change-user-type');
        if ($change_user_type.length > 0) {
          $change_user_type.find('.button').once('change-user-type--processed').on('click', function () {
            $change_user_type.hide();
            return false;
          });
        }

      });
    }
  };
  Drupal.behaviors.muserOnboarding = {
    attach: function (context, settings) {
      if ($('.block-system-main-block .user-form').length === 0) {
        return;
      }
      let hero = $('.main__hero', context);

      hero.addClass('main__hero--user-type');

      let hero_content = $('.hero__content', context);
      let region_hero_original = hero_content.find('.region-hero')
      let region_hero_new = region_hero_original.clone();

      let user_type_options = [];
      let type_markup = $('#edit-field-user-type .form-type-radio label');

      type_markup.each(function(label_key) {
        // console.log('label', type_markup[label_key]);
        let $label = $(type_markup[label_key]);
        let type_option = {
          id: $label.attr('for'),
          label: $label.find('.role--name').text(),
          description: $label.find('.role--description').text()
        }
        user_type_options.push(type_option)
      });
      let markup = '<div class="user-type-changer">';

      markup += user_type_options.map(function(option) {
        let ret = '';
        ret += '<div class="user-type-changer__type">';
        ret += '<button class="user-type-changer__label" data-type-input-id="' + option.id + '">' + option.label + '</button>';
        ret += '<p class="user-type-changer__description">' + option.description + '</p>';
        ret += '</div>';
        return ret;
      }).join('');

      markup += '</div>';

      region_hero_original.addClass('region-hero--original');
      region_hero_new.find('.page-title').html(Drupal.t('I am a...'));
      region_hero_new.append(markup);
      region_hero_new.addClass('region-hero--new');
      hero_content.append(region_hero_new)

      let main_content = $('.main__content', context);

      let activate_onboarding = function() {
        hero.addClass('main__hero--onboarding-active');
        main_content.addClass('main__content--disable');
      };

      $('#edit-change-use-type, #edit-change-use-type-after').on('click', function() {
        activate_onboarding();
      });

      if ($('#edit-field-user-type .form-type-radio input:checked').length === 0) {
        activate_onboarding();
      }

      $('.user-type-changer__label').on('click', function() {
        let $this = $(this);
        let id = $this.attr('data-type-input-id');
        let label = $this.text();
        $('#' + id).click();
        hero.removeClass('main__hero--onboarding-active')
        main_content.removeClass('main__content--disable');
        $('.change-user-type', context).css('display', '');
        $('.field--name-field-user-type .placeholder', context).html(label)
        $('.change-user-type', context).addClass('change-user-type--changed-once')
      })
    }
  }
})(jQuery, Drupal);
