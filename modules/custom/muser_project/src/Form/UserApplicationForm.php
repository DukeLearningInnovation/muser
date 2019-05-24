<?php

namespace Drupal\muser_project\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Provides the user application form.
 */
class UserApplicationForm extends FormBase {

  /**
   * The Project-Round node we're working with.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $projectRound;

  /**
   * The Flagging entity we're editing.
   *
   * @var \Drupal\flag\Entity\Flagging
   */
  protected $flaggingEntity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty($this->projectRound)) {
      throw new \Exception('Project-Round must be set with setProjectRound.');
    }
    $id = (!$this->flaggingEntity) ? 'null' : $this->flaggingEntity->id();
    return 'muser_project_user_application_' . $id;
  }

  /**
   * Set Project-Round.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Project-Round node.
   */
  public function setProjectRound(Node $node) {
    $this->projectRound = $node;
    $this->setFlaggingEntity($node);
  }

  /**
   * Set Flagging entity.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Project-Round node.
   */
  public function setFlaggingEntity(Node $node) {
    if ($this->currentUser()->isAnonymous()) {
      return;
    }
    $this->flaggingEntity = muser_project_get_flagging($node, NULL, $this->currentUser());
  }

  /**
   * Get title of associated project.
   *
   * @return mixed
   */
  public function getProjectLabel() {
    $project = $this->projectRound->field_project->entity;
    return $project->label();
  }

  /**
   * Get nid of associated project.
   *
   * @return mixed
   */
  public function getProjectNid() {
    $project = $this->projectRound->field_project->entity;
    return $project->id();
  }

  /**
   * Get number of applications allowed for Round.
   *
   * @return int
   */
  public function getNumAllowedApplications() {
    $round = $this->projectRound->field_round->entity;
    return $round->field_num_app_per_student->value;
  }

  /**
   * Return TRUE if the user has not exceeded max application submissions.
   *
   * @return bool
   */
  public function submissionCountOk() {
    return (!$this->getNumAllowedApplications() || $this->getNumAllowedApplications() > $this->getNumApplications());
  }

  /**
   * Return TRUE if date is OK to submit an application.
   *
   * @return bool
   */
  public function submissionDateOk() {
    $round = $this->projectRound->field_round->entity;
    $ok = muser_project_round_in_period($round->id(), 'application');
    return $ok;
  }

  /**
   * Return TRUE if the user can submit an application.
   *
   * @return bool
   */
  public function submissionAllowed() {
    return ($this->submissionCountOk() && $this->submissionDateOk());
  }

  /**
   * Get number of applications for this user.
   *
   * @return int
   */
  public function getNumApplications() {
    return muser_project_get_user_application_count($this->currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'muser_project/applications';

    if (!$this->currentUser()->isAuthenticated()) {
      $form['status'] = [
        '#type' => 'markup',
        '#markup' => '<div>'
        . $this->t('You must be logged in to submit an application.')
        . '</div>',
      ];
      return $form;
    }

    /** @var \Drupal\flag\FlaggingInterface $flagging */
    if (!$this->flaggingEntity) {
      $form['status'] = [
        '#type' => 'markup',
        '#markup' => '<div>'
        . $this->t('This project is no longer a Favorite.')
        . '</div>',
      ];
      return $form;
    }

    if (!$this->submissionDateOk()) {
      \Drupal::messenger()
        ->addWarning($this->t('Applications are not being accepted at this time.'));
    }

    $form['#project_round'] = $this->projectRound;
    $form['#flagging'] = $this->flaggingEntity;
    $essay = $this->flaggingEntity->field_essay->value;
    $submitted = $this->flaggingEntity->field_is_submitted->value;

    $form['#is_submitted'] = $submitted;
    $form['#attributes']['data-submitted'] = $submitted;
    $form['#attributes']['data-project-nid'] = $this->getProjectNid();
    $form['#attributes']['data-submitted-count'] = $this->getNumApplications();
    $form['#attributes']['class'][] = 'muser-project-user-application';
    $form['submitted_markup'] = [
      '#markup' => '<div class="submitted-markup--js submitted-markup--' . $this->getProjectNid() . '">'
      . muser_project_get_application_submitted_display()
      . muser_project_get_application_status_display(1, MUSER_STUDENT)
      . '</div>',
    ];

    if (!$this->submissionDateOk() || $submitted) {
      $class = (!$essay) ? 'essay-text--empty' : '';
      if (!$essay) {
        $essay = $this->t('- No essay -');
      }
      $form['essay'] = [
        '#type' => 'markup',
        '#markup' => '<div class="essay"><div class="label">'
        . $this->t('My Essay')
        . '</div><div class="essay-text ' . $class . '">'
        . nl2br(Html::escape($essay))
        . '</div></div>',
      ];
    }
    else {
      $form['essay'] = [
        '#type' => 'textarea',
        '#title' => $this->t('My Essay'),
        '#required' => TRUE,
        '#default_value' => $essay,
        '#rows' => 20,
        '#simple_element' => TRUE,
      ];
    }

    if ($this->submissionAllowed()) {
      $form['guidelines'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('muser_system.essay_guidelines'),
        '#title' => $this->t('Essay guidelines'),
        '#attributes' => [
          'class' => ['essay-guidelines', 'use-ajax'],
          'data-dialog-options' => '{"width":"auto"}',
          'data-dialog-type' => 'modal',
        ],
        '#attached' => [
          'library' => ['core/drupal.dialog.ajax'],
        ],
      ];
    }

    $ajax_id = Html::getId($this->getFormId() . '--wrapper');
    $form['#prefix'] = '<div class="application__essay-wrapper" id="' . $ajax_id . '">';
    $form['#suffix'] = '</div>';
    $ajax = [
      'callback' => '::processForm',
      'wrapper' => $ajax_id,
      'disable-refocus' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    if ($submitted) {
      if ($this->submissionDateOk()) {
        $form['actions']['draft'] = [
          '#type' => 'submit',
          '#value' => $this->t('Withdraw application'),
          '#button_type' => 'primary',
          '#submit' => ['::submitDraft'],
          '#ajax' => $ajax,
        ];
      }
    }
    else {
      if ($this->submissionAllowed()) {
        $form['actions']['save'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit application'),
          '#button_type' => 'primary',
          '#submit' => ['::submitForm'],
          '#ajax' => $ajax,
        ];
      }
      if ($this->submissionDateOk()) {
        $form['actions']['draft'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save as draft'),
          '#button_type' => 'primary',
          '#submit' => ['::submitDraft'],
          '#ajax' => $ajax,
        ];
      }

    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (!$form['#flagging']) {
      $form_state->setErrorByName('essay', $this->t('There was an error saving your essay.'));
      return;
    }

    if (!$this->submissionDateOk()) {
      $form_state->setErrorByName('essay', $this->t('Applications are not being accepted at this time.'));
    }

    if (isset($values['essay']) && !trim($values['essay'])) {
      $form_state->setErrorByName('essay', $this->t('Please enter your essay.'));
    }

    if (!empty($values['save'])
      && $values['save']->__toString() == $values['op']->__toString()
      && !$this->submissionAllowed()) {
      $message = $this->t('You may not submit this application because you have already submitted the maximum number of applications for this round (@num).', ['@num' => $this->getNumAllowedApplications()]);
      $form_state->setErrorByName('essay', $message);
    }

  } // End validateForm().

  public function processForm(array &$form, FormStateInterface $form_state) {

    $this->setFlaggingEntity($form['#project_round']);

    // Rebuild the form state values.
    $form_state->setRebuild();
    $form_state->setStorage([]);
    $form = \Drupal::service('form_builder')->rebuildForm($form['#form_id'], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form['#flagging']->field_essay = trim($values['essay']);
    $form['#flagging']->field_is_submitted = TRUE;
    $form['#flagging']->field_notification_sent = FALSE;
    $form['#flagging']->save();
    if (!$form['#is_submitted']) {
      // Clear the count cache.
      drupal_static_reset('muser_project_get_user_application_count');
      $cache = \Drupal::cache();
      $cache->delete(muser_project_get_application_count_cid($this->projectRound->id()));
      $cache->delete(muser_project_get_user_application_count_cid($this->currentUser()
        ->id()));
      // Clear the block's build array cache, too.
      Cache::invalidateTags(['application_count:' . $this->currentUser()->id()]);
      // Clear the review info block's build array cache.
      Cache::invalidateTags(['application_review_count:' . muser_project_get_flagged_project_mentor_id($this->flaggingEntity)]);
      // Clear the project's application count build array cache.
      Cache::invalidateTags(['project_application_count:' . $this->getProjectNid()]);
    }
    \Drupal::messenger()
      ->addStatus($this->t('Your application for %title has been submitted.', ['%title' => $this->getProjectLabel()]));
  } // End submitForm().

  /**
   * Save applications as a draft.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitDraft(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['essay'])) {
      $form['#flagging']->field_essay = trim($values['essay']);
    }
    $form['#flagging']->field_is_submitted = FALSE;
    $form['#flagging']->field_notification_sent = FALSE;
    $form['#flagging']->field_status = 'pending';
    $form['#flagging']->save();
    if ($form['#is_submitted']) {
      $message = $this->t('Your application for %title has been withdrawn.', ['%title' => $this->getProjectLabel()]);
      // Clear the count cache.
      drupal_static_reset('muser_project_get_user_application_count');
      $cache = \Drupal::cache();
      $cache->delete(muser_project_get_application_count_cid($this->projectRound->id()));
      $cache->delete(muser_project_get_user_application_count_cid($this->currentUser()
        ->id()));
      // Clear the block's build array cache, too.
      Cache::invalidateTags(['application_count:' . $this->currentUser()->id()]);
      // Clear the review info block's build array cache.
      Cache::invalidateTags(['application_review_count:' . muser_project_get_flagged_project_mentor_id($this->flaggingEntity)]);
      // Clear the project's application count build array cache.
      Cache::invalidateTags(['project_application_count:' . $this->getProjectNid()]);
    }
    else {
      $message = $this->t('Your application for %title has been saved as a draft.', ['%title' => $this->getProjectLabel()]);
    }
    \Drupal::messenger()->addStatus($message);
  } // End submitDraft().

}
