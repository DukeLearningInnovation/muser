<?php

namespace Drupal\muser_project\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flag\FlaggingInterface;
use Drupal\muser_project\CancelUrlTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides a Muser Project form.
 */
class AcceptContractForm extends FormBase {

  use CancelUrlTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'muser_project_accept_contract';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlaggingInterface $flagging = NULL) {

    $config = \Drupal::config('muser_system.settings');

    if (!$flagging) {
      $form['status'] = [
        '#type' => 'markup',
        '#markup' => '<div>'
        . $this->t('Error retrieving contract.')
        . '</div>',
      ];
      return $form;
    }

    $round = muser_project_get_round_for_flagging($flagging);
    if (!$round || !muser_project_round_in_period($round->id(), 'contract')) {
      $form['status'] = [
        '#type' => 'markup',
        '#markup' => '<div>'
        . $this->t('Contract status cannot be updated now.')
        . '</div>',
      ];
      return $form;
    }

    $form['#flagging'] = $flagging;

    $project = muser_project_get_project_for_flagging($flagging);
    $form['project'] = [
      '#type' => 'markup',
      '#markup' => '<div class="field"><div class="label">'
      . $this->t('Project')
      . '</div><div class="value">'
      . MUSER_CONTRACT_ICON . ' '
      . Html::escape($project->label())
      . '</div></div>',
    ];
    $form['mentor'] = [
      '#type' => 'markup',
      '#markup' => '<div class="field"><div class="label">'
      . $this->t('Mentor')
      . '</div><div class="value">'
      . Html::escape($project->getOwner()->label())
      . '</div></div>',
    ];
    $form['student'] = [
      '#type' => 'markup',
      '#markup' => '<div class="field"><div class="label">'
      . $this->t('Student')
      . '</div><div class="value">'
      . Html::escape($flagging->getOwner()->label())
      . '</div></div>',
    ];

    if ($flagging->getOwnerId() != $this->currentUser()->id()) {
      // Mentor.
      $form['#accepted_field'] = 'field_contract_signed_mentor';
      $form['#date_field'] = 'field_contract_date_mentor';
      $form['#redirect_url'] = Url::fromRoute('view.applications.page_accepted', ['user' => $this->currentUser()->id()]);
    }
    else {
      // Student.
      $form['#accepted_field'] = 'field_contract_signed_student';
      $form['#date_field'] = 'field_contract_date_student';
      $form['#redirect_url'] = Url::fromRoute('view.my_favorites.page', ['user' => $this->currentUser()->id()]);
    }

    $options = [
      '1' => $this->t('Yes'),
      '0' => $this->t('No'),
    ];
    $form['accepted'] = [
      '#type' => 'radios',
      '#title' => $this->t('Have you accepted a contract for this project?'),
      '#required' => TRUE,
      '#default_value' => $flagging->{$form['#accepted_field']}->value ?? 0,
      '#options' => $options,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update contract status'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];
    $form['actions']['cancel'] = $this->buildCancelLink($this->getRequest(), $form['#redirect_url']);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form['#flagging']) {
      $form_state->setErrorByName('status', $this->t('There was an error updating this contract.'));
      return;
    }
  } // End validateForm().

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($accepted = $form_state->getValue('accepted')) {
      // Set contract date.
      $now = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);
      $form['#flagging']->{$form['#date_field']}->value = $now->format('Y-m-d\TH:i:s');
    }
    else {
      // Unset contract date.
      $form['#flagging']->{$form['#date_field']}->value = NULL;
    }

    $form['#flagging']->{$form['#accepted_field']} = $accepted;
    $form['#flagging']->save();
    $status_text = $form['#status_options'][$accepted] ?? NULL;

    // Clear the review info block's build array cache.
    Cache::invalidateTags(['application_review_count:' . $this->currentUser()->id()]);

    \Drupal::messenger()->addStatus($this->t('Contract status updated.'));
    $form_state->setRedirectUrl($form['#redirect_url']);

  } // End submitForm().

}
