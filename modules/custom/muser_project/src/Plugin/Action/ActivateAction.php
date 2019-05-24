<?php

namespace Drupal\muser_project\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Action to activate a project for a/the current round.
 *
 * @Action(
 *   id = "muser_project_activate_project",
 *   label = @Translation("Activate this project in current round."),
 *   type = "node",
 *   confirm = TRUE,
 * )
 */
class ActivateAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    // Current Round
    $round = muser_project_get_current_round();
    if (!$round) {
      return $this->t('There is no current round.');
    }

    $pr = muser_project_get_project_round_for_project($entity->id(), $round);
    if ($pr) {
      return $this->t('@label already in the current round.', ['@label' => $entity->label()]);
    }

    if (!muser_project_add_project_to_round($entity->id(), $round)) {
      return $this->t('Problem adding @label to the current round.', ['@label' => $entity->label()]);
    }

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Project activated in current round.');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    // Check if this is a project entity.
    if ($object->getEntityType()->id() === 'node'  && $object->bundle() == 'project') {
      // Check if the user as edit access to the project
      $access = $object->access('update', $account, TRUE);

      // Check secondary permissions
      if ($access->isAllowed()) {
        if (!$account->hasPermission('administer project rounds')) {
          // Check if in posting period.
          $round_nid = muser_project_get_current_round();

          // If not in the posting period then return false
          if (!$round_nid || !muser_project_round_in_period($round_nid)) {
            return FALSE;
          }
        }
      }

      return $return_as_object ? $access : $access->isAllowed();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $results = parent::executeMultiple($objects);

    // Clear the static var that holds the rounds for projects.
    drupal_static('muser_project_get_project_round_for_project', [], TRUE);

    return $results;
  }

}