/**
 * @file
 * Project list functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserApplications = {
    attach: function (context, settings) {

      let num_submitted = 0;
      let is_changed = 0;
      $('.muser-project-user-application').once('application-status-processed').each(function () {
        let form_status = parseInt($(this).attr('data-submitted'));
        let form_nid = $(this).attr('data-project-nid');
        let $status = $('article.node--nid--' + form_nid).find('.project__application-status .application-status--submitted');
        let markup_status = ($status.length > 0 ? 1 : 0);
        if (markup_status === form_status) {
          return;
        }
        is_changed = 1;
        num_submitted = parseInt($(this).attr('data-submitted-count'));
        if (form_status === 0) {
          $status.parent().html('');
        }
        else {
          let status = $('article.node--nid--' + form_nid).find('.project__application-status');
          status.show();
          let status_markup = $('.submitted-markup--' + form_nid).html();
          status.html(status_markup);
        }
      });

      if (is_changed) {
        $('.block-muser-application-count .submitted-text .placeholder').first().html(num_submitted);
      }

      let application_body = $('.application__body', context);
      application_body.find('a, input, textarea').attr('tabindex', -1);

      let toggle = '<button class="toggle-application application--closed"><span><i class="fas fa-chevron-down"></i></span><span style="display:none;"><i class="fas fa-chevron-up"></i></span></button>';

      let setHeight = function (application) {
        let $application = $(application);
        let height = $application.find('.application__body-height').height();
        $application.find('.application__body').css({maxHeight: height});
      };

      let $applications = $('article.application-collapsible', context);
      $applications.once('application--processed').addClass('application--closed').each(function () {
        $(this).find('.application__main').prepend(toggle);
        setHeight(this)
      });

      let resizing = false;
      $(window).resize(function () {
        if (resizing) {
          return;
        }
        else {
          resizing = true;
          $applications.each(function () {
            setHeight(this)
          });
          setTimeout(function () {
            resizing = false;
            $applications.each(function () {
              setHeight(this)
            });
          }, 100)
        }
      });

      var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
      var observer = new MutationObserver(mutationHandler);
      var obs_config = {childList: true, characterData: false, attributes: false, subtree: true};

      $('.application__essay-wrapper').each(function() {
        observer.observe (this, obs_config);
      });

      function mutationHandler (mutationRecords) {
        mutationRecords.forEach ( function (mutation) {
          if (resizing) {
            return;
          }
          else {
            let $applications = $(mutation.target).parents('article.application-collapsible');
            resizing = true;
            setTimeout(function () {
              resizing = false;
              $applications.each(function () {
                setHeight(this)
              });
            }, 100)
            $applications.each(function () {
              setHeight(this)
            });
          }
        });
      }


      $('.project__internal-wrapper, .application__body, .application__details-bar').once('application-open-processed').on('click', function(e) {
        let application_wrapper = $(this).parents('article.application-collapsible');
        if (application_wrapper.hasClass('application--open')) {
          // we only want to open the application by clicking anywhere.
          return;
        }
        let application_body = application_wrapper.find('.application__body');
        application_body.find('a, input, textarea').attr('tabindex', 0);

        // if ($(e.target)) {
        //   console.log('e.target', e.target);
        // }
        toggle_application(application_wrapper);
      });

      $('.toggle-application', context).once('application--processed').each(function () {
        $(this).on('click', function () {
          let application_wrapper = $(this).parents('article.application-collapsible');
          toggle_application(application_wrapper);
          return false;
        });
      });

      let toggle_application = function(application) {
        application.toggleClass('application--closed application--open');
        let toggle_application_button = application.find('.toggle-application');

        toggle_application_button.toggleClass('application--closed application--open')
          .find('span').toggle();

        let application_body = application.find('.application__body');

        if (toggle_application_button.hasClass('application--closed')) {
          application_body.find('a, input, textarea').attr('tabindex', -1);
        }
        else {
          application_body.find('a, input, textarea').attr('tabindex', 0);
        }

        return false;
      }

    }
  };
})(jQuery, Drupal);
