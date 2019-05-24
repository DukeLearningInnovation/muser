<?php

namespace Drupal\muser_system\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\DeleteMultiple;

/**
 * Provides a node deletion confirmation form.
 *
 * @internal
 */
class MuserNodeDeleteMultiple extends DeleteMultiple {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $round_entities = [];
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $original = $this->selection;

    $entities = $storage->loadMultiple(array_keys($this->selection));
    foreach ($this->selection as $id => $selected_langcodes) {
      $entity = $entities[$id];

      // Overriding for round only.
      if ($entity->bundle() == 'round') {
        $round_entities[$id] = $entity;
        unset($this->selection[$id]);
      }
    }

    if ($round_entities) {
      $this->messenger->addWarning($this->getNoRoundsMessage(count($round_entities)));
    }

    $return = parent::submitForm($form, $form_state);
    $this->selection = $original;

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNoRoundsMessage($count) {
    return $this->formatPlural($count, "A selected round has not been deleted because bulk deletion of rounds is not allowed.", "@count rounds have not been deleted, bulk deletion of rounds is not allowed.");
  }

}
