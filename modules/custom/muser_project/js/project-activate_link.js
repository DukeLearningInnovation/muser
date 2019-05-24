(function ($) {
(function (Drupal) {
  Drupal.behaviors.projectActivateAttach = {
    attach: function attach(context) {
      var links = context.querySelectorAll('.project-activate a');
      links.forEach(function (link) {
        return link.addEventListener('click', function (event) {
          // Use Take advangage of what the flag module does here.
          return event.target.parentNode.classList.add('flag-waiting');
        });
      });
    }
  };

  Drupal.behaviors.projectContextualMenu = {
    attach: function attach(context) {

      var contextual_menus = $('.project__contextual-links', context).once('contextual-links--processed')
        .on('click', function(event) {
          let $this = $(this);
          contextual_menus.not(this).removeClass('contextual-links--open');
          $this.toggleClass('contextual-links--open');
          if ($this.hasClass('contextual-links--open')) {
            contextual_menus.find('a').attr('tabindex', 0);
          }
          else {
            contextual_menus.find('a').attr('tabindex', -1);
          }
          // event.preventDefault();
          // event.stopPropagation();
        });
      contextual_menus.find('a').attr('tabindex', -1);

      window.addEventListener('keyup', function(e) {
        e = e || window.event;
        let isEscape = false;
        if ("key" in e) {
          isEscape = (e.key === "Escape" || e.key === "Esc");
        } else {
          isEscape = (e.keyCode === 27);
        }
        if (isEscape) {
          $('.project__contextual-links', context).removeClass('contextual-links--open');
          contextual_menus.find('a').attr('tabindex', -1);
        }
      }, false);
      $(document).mouseup(function(e) {
        // if the target of the click isn't the container nor a descendant of the container
        contextual_menus.each(function(k, contextual_menu) {
          let $contextual_menu = $(contextual_menu);
          if ($contextual_menu.hasClass('contextual-links--open') && !$contextual_menu.is(e.target) && $contextual_menu.has(e.target).length === 0) {
            $contextual_menu.removeClass('contextual-links--open');
            $contextual_menu.find('a').attr('tabindex', -1);
          }
        })
      });
    }
  };

  Drupal.AjaxCommands.prototype.updateProjectStatus = function (ajax, response, status) {
    if (status === 'success') {

      var parent = $(response.selector).parents('.project__contextual-links').next('.node--type-project');
      parent.removeClass('project--inactive');
      parent.removeClass('project--active');
      parent.addClass(response.class);

    }
  };

})(Drupal);
}(jQuery));
