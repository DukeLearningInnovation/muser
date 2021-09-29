/**
 * @file
 * View filter functionality.
 */

(function ($, Drupal) {
  Drupal.behaviors.muserContractConfirm = {
    attach: function (context, settings) {
      const $contract_fields = $('.field--name-field-use-contract', context);
      let allow_checking = false;
      $contract_fields.once('muser-contract-confirm--processed').each(function(field) {
        const $contract_field = $(this);
        if ($contract_field.attr('data-enable-contract-modal') === '1') {
          const $checkbox = $contract_field.find('input[type="checkbox"]');
          $checkbox.on('change', function(e) {
            if (e.originalEvent !== undefined && this.checked && !allow_checking) {
              this.checked = false;
              openConfirmationModal($contract_field, $checkbox)
              return;
            }
            allow_checking = false
          })
        }
      })

      let openConfirmationModal = function($contract_field, $checkbox) {
        $contract_field.attr('data-contract-modal-text')

        $contract_field.attr('data-require-confirmation-text')
        const trimmed_confirm_text = $contract_field.attr('data-contract-short-confirmation-text').toLocaleLowerCase().trim();

        $contract_field.attr('data-contract-modal-text-confirm')
        $('.contract-confirm-modal').remove();

        const markup = `
        <div class="contract-confirm-modal">
        <div class="contract-confirm-modal__window">
            <div class="contract-confirm-modal__text">${$contract_field.attr('data-contract-modal-text')}</div>
            ${$contract_field.attr('data-require-confirmation-text') === "1" ?
          `<label for="contract-confirm-modal__input" class="contract-confirm-modal__confirm-text">
              ${$contract_field.attr('data-contract-modal-text-confirm')}
           </label>
          <input  autocomplete="off" id="contract-confirm-modal__input" class="contract-confirm-modal__confirm-field" />
          ` : ""}
           
           <div class="contract-confirm-modal__actions">
             <button class="contract-confirm-modal__action-cancel button--outline">${Drupal.t('Cancel')}</button>
             <button class="contract-confirm-modal__action-submit button--outline" ${$contract_field.attr('data-require-confirmation-text') === "1" ? 'disabled' : ''}>${Drupal.t('Accept')}</button>
            </div>
        
      </div>
      </div>
        `;
        const $markup = $(markup);
        $contract_field.append($markup);
        const $confirmation_field = $markup.find('.contract-confirm-modal__confirm-field');
        const $submit_button = $markup.find('.contract-confirm-modal__action-submit');
        const $cancel_button = $markup.find('.contract-confirm-modal__action-cancel');
        if ($confirmation_field.length > 0) {
          $confirmation_field.focus();
        }
        $confirmation_field.on('change blur keydown keyup', function() {
          if ($confirmation_field.val().toLocaleLowerCase().trim() === trimmed_confirm_text) {
            $submit_button.removeAttr('disabled')
          }
          else {
            $submit_button.attr('disabled', 'disabled')
          }
        })
        $submit_button.on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          if ($contract_field.attr('data-require-confirmation-text') !== "1" || $confirmation_field.val().toLocaleLowerCase().trim() === trimmed_confirm_text) {
            allow_checking = true;
            $checkbox.click();
            $markup.remove();
          }
        })
        $cancel_button.on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $markup.remove();
        })
      }
    }
  };
})(jQuery, Drupal);
