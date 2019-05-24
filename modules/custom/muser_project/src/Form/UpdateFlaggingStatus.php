<?php

namespace Drupal\muser_project\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;

/**
 * Provides the form to update application status.
 */
class UpdateFlaggingStatus extends FormBase {

  const ESSAY_SNIPPET_LENGTH = 100;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'muser_project_update_flagging_status';
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
        . $this->t('Error retrieving application.')
        . '</div>',
      ];
      return $form;
    }

    $round = muser_project_get_round_for_flagging($flagging);
    if (!$round || !muser_project_round_in_period($round->id(), 'acceptance')) {
      $form['status'] = [
        '#type' => 'markup',
        '#markup' => '<div>'
        . $this->t('Application status cannot be updated now.')
        . '</div>',
      ];
      return $form;
    }

    $form['#flagging'] = $flagging;

    $project = muser_project_get_project_for_flagging($flagging);
    $form['project'] = [
      '#type' => 'markup',
      '#markup' => '<div class="label">'
      . Html::escape($project->label())
      . '</div>',
    ];

    $essay = $flagging->field_essay->value;
    if (strlen($essay) > self::ESSAY_SNIPPET_LENGTH) {
      $essay_snippet = substr($essay, 0, self::ESSAY_SNIPPET_LENGTH) . '...';
    }
    else {
      $essay_snippet = $essay;
    }
    $form['essay'] = [
      '#type' => 'markup',
      '#markup' => '<div class="essay"><div class="label">'
      . $this->t('Essay')
      . '</div><div class="essay-text">'
      . nl2br(Html::escape($essay_snippet))
      . '</div></div>',
    ];

    $definition = FieldConfig::loadByName('flagging', 'favorites', 'field_status');
    $settings = $definition->getSettings();
    $options = $settings['allowed_values'];
    $form['#status_options'] = $options;
    $current_status = $flagging->field_status->value;

    if ($current_status == 'pending') {
      // If it's "pending", they can't accept it yet.
      unset($options['accepted']);
    }
    else {
      // Remove "pending" option (can't go back to that).
      unset($options['pending']);
    }

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();
    $muser_data['round'] = muser_project_get_current_round(TRUE);

    foreach ($options as $key => &$text) {
      $name = 'application_status_' . $key;
      $text = '<div class="status-text">' . $text . '</div>';
      if ($value = $config->get($name)) {
        $text .= '<div class="description">'
          . check_markup($value['value'], $value['format'])
          . '</div>';
        $text = $token_service->replace($text, [
          'user' => \Drupal::currentUser(),
          'muser' => $muser_data,
        ]);
      }
    } // Loop thru status values.

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#required' => TRUE,
      '#default_value' => $current_status,
      '#options' => $options,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change status'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];
    $cancel_url = Url::fromRoute('view.applications.page_new', ['user' => $this->currentUser()->id()]);
    $form['actions']['cancel'] = $this->buildCancelLink($this->getRequest(), $cancel_url);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form['#flagging']) {
      $form_state->setErrorByName('status', $this->t('There was an error updating this application.'));
      return;
    }
  } // End validateForm().

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $status = $form_state->getValue('status');
    if ($form['#flagging']->field_status->value != $status) {
      // If flag status has changed, reset notification status.
      $form['#flagging']->field_notification_sent = FALSE;
    }
    $form['#flagging']->field_status = $status;
    $form['#flagging']->save();
    $status_text = $form['#status_options'][$status] ?? NULL;

    // Clear the review info block's build array cache.
    Cache::invalidateTags(['application_review_count:' . $this->currentUser()->id()]);

    if ($status_text) {
      \Drupal::messenger()->addStatus($this->t('Application status is now %status.', ['%status' => $status_text]));
    }
    else {
      \Drupal::messenger()->addStatus($this->t('Application status updated.'));
    }
    $form_state->setRedirectUrl(Url::fromRoute('view.applications.page_new', ['user' => $this->currentUser()->id()]));
  } // End submitForm().

  /**
   * Builds the cancel link for a confirmation form.
   *
   * Based on ConfirmFormHelper::buildCancelLink()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $cancel_url
   *   The default url for cancel.
   *
   * @return array
   *   The link render array for the form.
   */
  public function buildCancelLink(Request $request, $cancel_url) {
    // Prepare cancel link.
    $query = $request->query;
    $url = NULL;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      // @todo Revisit this in https://www.drupal.org/node/2418219.
      try {
        $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
        // Suppress the exception and fall back to the form's cancel url.
      }
    }
    // Check for a route-based cancel link.
    if (!$url) {
      $url = $cancel_url;
    }
    return [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
  }

}
