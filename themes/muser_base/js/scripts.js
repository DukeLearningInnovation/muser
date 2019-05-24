(function($) { // Begin jQuery
  Drupal.behaviors.muserCheckboxes = {
    attach: function (context, settings) {
      let checkboxes = $('.checkbox__wrapper input', context);
      checkboxes.once('checkbox__input--processed').on('change keyup keydown onfocusout focus blur', function (e) {
        let $this = $(this);
        $this.parent().removeClass('checked').addClass($this.is(':checked') ? 'checked' : '')
      })
    }
  };
  Drupal.behaviors.muserForms = {
    attach: function (context, settings) {
      let form_items = $('.js-form-item input, .js-form-item textarea', context);
      form_items.on('change keyup keydown onfocusout focus blur', function (e) {
        let $this = $(this);
        if ((e.type === 'blur' || e.type === 'change') && !$this.val()) {
          $this.parents('.js-form-item').removeClass('item-filled');
          return;
        }
        $this.parents('.js-form-item').addClass('item-filled');
      });
      let load_filled_classes = function() {
        let form_items = $('form:not(.views-exposed-form) .js-form-item input, form:not(.views-exposed-form) .js-form-item textarea', context);
        form_items.each(function () {
          $(this).change();
        });
      };
      load_filled_classes();
      $(document).ready(function() {
        load_filled_classes();
        setTimeout(load_filled_classes, 500)
      })
    }
  }
  Drupal.behaviors.muserTooltipClose = {
    attach: function (context, settings) {
      let closeable_tooltips = $('.tooltip__close', context).once('tooltip__close--processed').click(function(e) {
        e.preventDefault();
        $(this).parents('.tooltip').addClass('tooltip--closed')
      });

    }
  }
  Drupal.behaviors.muserMobileMenu = {
    attach: function (context, settings) {
      let header_menus = $('.header__menus');
      let header_menu_region = $('.region-header-menu');
      let updateWindowSizeTabIndexes = function() {
        if (window.matchMedia('(max-width: 991px)').matches) {
          header_menus.find('a').attr('tabindex', -1);
        }
        else {
          header_menus.find('a').attr('tabindex', '0');
        }
      };
      updateWindowSizeTabIndexes();
      let allow_menu_call = true;
      $(window).on('resize', function() {
        if (allow_menu_call) {
          allow_menu_call = false;
          setTimeout(function() {
            allow_menu_call = true;
            updateWindowSizeTabIndexes()
          }, 100)
        }
      })


      let menu_toggle = $('.header__menu-toggle', context).once('header__menu-toggle--processed').click(function(e) {
        e.preventDefault();
        header_menu_region.toggleClass('header-menu--open');
        if (header_menu_region.hasClass('header-menu--open')) {
          header_menus.find('a').attr('tabindex', 0);
        }
        else {
          header_menus.find('a').attr('tabindex', -1);
        }
      });
      window.addEventListener('keyup', function(e) {
        e = e || window.event;
        let isEscape = false;
        if ("key" in e) {
          isEscape = (e.key === "Escape" || e.key === "Esc");
        } else {
          isEscape = (e.keyCode === 27);
        }
        if (isEscape) {
          $('.region-header-menu').removeClass('header-menu--open');
          header_menus.find('a').attr('tabindex', -1);
        }
      }, false);
      $(document).mouseup(function(e) {
        // if the target of the click isn't the container nor a descendant of the container
        if (header_menu_region.hasClass('header-menu--open') && !header_menu_region.is(e.target) && header_menu_region.has(e.target).length === 0) {
          header_menu_region.removeClass('header-menu--open');
          header_menus.find('a').attr('tabindex', -1);
        }
      });
    }
  }

  Drupal.behaviors.muserYoDawgIHeardYouLikeModals = {
    attach: function (context, settings) {
      let confirmation_forms = $('.unflag-confirm-form.confirmation').once('confirmation-form--processed');
      let destination_regex = /^.*\?.*(destination=([^&\n\r]+_wrapper_format(%3D|=)drupal_modal[^&\n\r]*)).*?$/
      let form_action_replacer = function(match, full_query_item, destination, equal, offset, string) {
        return string.replace(destination, encodeURIComponent(window.location.pathname + window.location.search));
      };

      let link_regex = /(^.*)(\?.*)(_wrapper_format(%3D|=)drupal_modal&?)(.*?)$/
      let link_href_replacer = function(match, path, qs_before, qs_modal, equals, qs_after, offset, string) {
        let new_qs = window.location.search.replace(/^\?/, '');
        if (new_qs.length !== 0) {
          new_qs += '&'
        }
        return string.replace(path, window.location.pathname).replace(qs_modal, new_qs);
      };

      confirmation_forms.each(function(key) {
        let confirmation_form = $(confirmation_forms[key]);
        let action = confirmation_form.attr('action');

        // the following regex updates destination urls that would send you to a ajax data page.
        let new_action = action.replace(destination_regex, form_action_replacer);
        confirmation_form.attr('action', new_action);

        // Also update any non-ajax links that return ajax data.
        let non_ajax_links = $('a:not(.use-ajax)', confirmation_form);
        non_ajax_links.each(function(key) {
          let link = $(non_ajax_links[key]);
          let new_href = link.attr('href').replace(link_regex, link_href_replacer);
          link.attr('href', new_href);
        })
      })
    }
  }
  Drupal.behaviors.muserDropdownSideSelection = {
    attach: function (context, settings) {
      let all_details = $('details');

      let update_side_select = function(detail) {
        let $details = $(detail);
        let $details_wrapper = $('.details-wrapper', detail);
        let rect_det = $details[0].getBoundingClientRect();
        let rect_det_wrap = $details_wrapper[0].getBoundingClientRect();
        let window_width = $(window).width();
        if (rect_det.left + rect_det_wrap.width >= window_width) {
          $details_wrapper.addClass('details-wrapper--fall-right');
        } else {
          $details_wrapper.removeClass('details-wrapper--fall-right');
        }
      }

      all_details.once('details--drop-processed').each(function(detail_key) {
        let detail = all_details[detail_key];
        update_side_select(detail);
        let allow_call = true;
        $(window).on('resize', function() {
          if (allow_call) {
            allow_call = false;
            setTimeout(function() {
              allow_call = true;
              update_side_select(detail)
            }, 100)
          }
        })
      });
      all_details.once('details--click-processed').on('toggle', function() {
        update_side_select($(this));
      })
    }
  }
  Drupal.behaviors.muserNoVertFlexProjects = {
    attach: function (context, settings) {
      if (navigator.userAgent.indexOf("Chrome/") === -1 &&
        navigator.userAgent.indexOf("Safari/") !== -1 &&
        navigator.userAgent.indexOf("Version/10.") !== -1) {
        $(body).addClass('no-vert-flex-projects')
      }
    }
  }
  Drupal.behaviors.muserHoverTooltips = {
    attach: function (context, settings) {
      let tooltips = $('.multiple-terms', context).once('multiple-terms--processed')
        .attr('tabindex', "0")
        .on('mouseenter focus', function(e) {
          let $this = $(this);
          $this.addClass('tooltip--active');
        })
        .on('mouseleave blur', function(e) {
          $(this).removeClass('tooltip--active');
        });
      window.addEventListener('keyup', function(e) {
        e = e || window.event;
        let isEscape = false;
        if ("key" in e) {
          isEscape = (e.key === "Escape" || e.key === "Esc");
        } else {
          isEscape = (e.keyCode === 27);
        }
        if (isEscape) {
          tooltips.removeClass('tooltip--active');
        }
      }, false);
    }
  }
})(jQuery);
