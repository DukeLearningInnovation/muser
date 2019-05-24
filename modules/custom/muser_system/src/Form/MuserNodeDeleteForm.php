<?php

namespace Drupal\muser_system\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\NodeDeleteForm;

/**
 * Provides a form for deleting a node.
 *
 * @internal
 */
class MuserNodeDeleteForm extends NodeDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->getEntity();

    // Overriding for round only.
    if ($entity->bundle() != 'round') {
      return $form;
    }

    $form['verify'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type DELETE here'),
      '#description' => $this->t('Type the word DELETE in the box to confirm that you would like to delete this round.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->getEntity();

    // Overriding for round only.
    if ($entity->bundle() == 'round') {
      $verify = $form_state->getValue('verify');
      if ($verify != 'DELETE') {
        // Set an error for the form element with a key of "title".
        $form_state->setErrorByName('verify', $this->t('You must type the word DELETE to confirm.'));
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->getEntity();

    // Overriding for round only.
    if ($entity->bundle() != 'round') {
      return parent::getDescription();
    }

    $counts = $this->projectsApplicationsCount($entity);

    return $this->t('Are you sure you want to delete this Round? It has @projects projects and @applicants applicants. ', [
      '@projects' => $counts['projects'],
      '@applicants' => $counts['applications']
    ]);
  }

  protected function projectsApplicationsCount($entity) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'project_round')
      ->condition('field_round', $entity->id())
      ->execute();

    $applications = 0;
    if (count($nids)) {
      $applications = \Drupal::entityQuery('flagging')
        ->condition('flag_id', 'favorites')
        ->condition('entity_type', 'node')
        ->condition('entity_id', $nids, 'IN')
        ->condition('field_is_submitted', 1)
        ->count()
        ->execute();
    }

    return [
      'projects' => count($nids),
      'applications' => $applications
    ];
  }
}
