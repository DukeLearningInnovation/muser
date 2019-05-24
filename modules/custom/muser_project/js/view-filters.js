/**
 * @file
 * View filter functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserProjectsSearchSubmit = {
    attach: function (context, settings) {
      let view_filters = $('.muser-view-filters');
      let search_button = $('.search__icon', view_filters);
      let filter_submit = $('.form-submit.button-submit', view_filters);
      search_button.once('search_button--processed').on('click', function () {
        filter_submit.click();
        return false;
      });

      let $resetButton = $('.muser-view-filters .button-reset');
      $resetButton.show();
      if ($('.muser-view-filters input:checkbox:checked').length === 0 && !$('input#edit-search').val()) {
        $resetButton.hide();
      }
    }
  };
  Drupal.behaviors.muserViewFilters = {
    attach: function (context, settings) {
      $('.muser-view-filters .form-item-search, .muser-view-filters details')
        .once('muser-view-filters-wrapper--applied')
        .wrapAll('<div class="muser-view-filters-wrapper"></div>');

      let $submitButton = $('.muser-view-filters .button-submit');
      $submitButton.hide();
      let $resetButton = $('.muser-view-filters .button-reset');
      if ($('.muser-view-filters input:checkbox:checked').length === 0 && !$('input#edit-search').val()) {
        $resetButton.hide();
      }

      let getCheckedValues = function ($detailsElement) {
        let $checked = $detailsElement.find('input:checkbox:checked');
        if ($checked.length === 0) {
          return '';
        }
        else {
          return $checked.map(function () {
            return $(this).val();
          }).get().join('|');
        }
      };

      let setDetailsSummary = function ($detailsElement) {
        let $summary = $detailsElement.find('summary');
        let $checked = $detailsElement.find('input:checkbox:checked');
        let $clear = $detailsElement.find('.filter__action--clear');
        if ($checked.length === 0) {
          $clear.hide();
          $summary.html($summary.attr('data-default'));
          $summary.removeClass('summary--has-selected')
        }
        else if ($checked.length > 1) {
          $clear.show();
          $summary.addClass('summary--has-selected');
          $summary.html($summary.attr('data-default') + ' (' + $checked.length + ')');
        }
        else {
          $clear.show();
          $summary.addClass('summary--has-selected');
          $summary.html($checked.parents('.checkbox__wrapper').parent().find('label').text());
        }
      };

      let clearOverlay = function () {
        $('.filter-overlay').removeClass('filter-overlay--active');
      };

      let clearLink = '<button type="button" class="filter__action filter__action--clear">' + Drupal.t('Clear') + '</button>';
      let submitLink = '<button type="button" class="filter__action filter__action--submit">' + Drupal.t('Submit') + '</button>';
      let filters = '<div class="filter__actions">' + clearLink + submitLink + '</div>';
      $('body').once('filter-overlay--processed')
          .prepend('<div class="filter-overlay"></div>');
      $('.filter-overlay').once('filter-overlay--processed')
          .on('click', function () {
            clearOverlay();
            $('.muser-view-filters details[open]').removeAttr('open')
          });

      $('details').once('details--processed').on('click', function () {
        $(this).siblings('details').removeAttr('open');
      });

      $('.muser-view-filters details').once('muser-filters--processed').each(function () {
        let $details = $(this);
        $(this).attr('data-values', getCheckedValues($(this)));

        $(this).on('toggle', function () {
          if ($(this).prop('open')) {
            $('.filter-overlay').addClass('filter-overlay--active');
          }
          else {
            // Hide overlay.
            clearOverlay();
            if ($('.muser-view-filters input:checkbox:checked').length > 0) {
              $resetButton.show();
            }
            if ($details.attr('data-values') !== getCheckedValues($details)) {
              // Values have changed.
              $details.attr('data-values', getCheckedValues($details));
              $submitButton.click();
            }
          }
        });

        $(this).find('.details-wrapper').append(filters);
        let $summary = $(this).find('summary');
        $summary.attr('data-default', $summary.html());
        setDetailsSummary($(this));

      });

      $('.muser-view-filters').once('muser-view-filters--processed').each(function () {

        $('.muser-view-filters details button.filter__action--submit').once('submitted--processed').on('click', function (e) {
          setDetailsSummary($(this).parents('details'));
          $(this).parents('details').removeAttr('open');
        });

        $('.muser-view-filters details button.filter__action--clear').once('clear-button--processed').on('click', function (e) {
          $(this).parents('.details-wrapper').find('input.form-checkbox').prop('checked', false);
          setDetailsSummary($(this).parents('details'));
        });

        $('.muser-view-filters details input:checkbox').once('checkbox--processed').on('change', function () {
          setDetailsSummary($(this).parents('details'));
        });

      });

    }
  };

  Drupal.behaviors.muserProjectFilterCategories = {
    attach: function (context, settings) {
      let updateActiveTag = function (active_tids, term_wrapper) {
        if (active_tids.length < 1) {
          return;
        }
        let available_terms = $('.extra-term', term_wrapper).map(function (k, term) {
          let $term = $(term);
          return {
            tid: $term.attr('data-extra-term-id'),
            term_value: $term.find('.term').html(),
          }
        });
        if (available_terms.length === 0) {
          // Item has only the one term that's shown.
          return;
        }
        let new_term = available_terms.filter((k, item) => active_tids.toArray().find(el => el === item.tid));
        if (new_term.length === 0) {
          return;
        }
        $('.active-term__term', term_wrapper).html(new_term[0].term_value)
      };

      let projects = $('.node--type-project.node--view-mode-teaser').once('project--filters-updated')
      $.each(projects, function (k, project) {
        let $project = $(project);
        let hours_options_selected = $('.details--hours input[type="checkbox"]:checked').map((k, checkbox) => $(checkbox).attr('value'));
        updateActiveTag(hours_options_selected, $project.find('.field--name-field-hours-per-week'))
        let cats_options_selected = $('.details--categories input[type="checkbox"]:checked').map((k, checkbox) => $(checkbox).attr('value'));
        updateActiveTag(cats_options_selected, $project.find('.field--name-field-categories'))
      });
    }
  }
})(jQuery, Drupal);
