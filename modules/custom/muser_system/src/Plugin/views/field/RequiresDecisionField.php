<?php

namespace Drupal\muser_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\Boolean;

/**
 * Field handler to show if there are applications that require a decision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("requires_decision")
 */
class RequiresDecisionField extends Boolean {

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField(NULL, '(' . $this->tableAlias . '.no_decision > 0)', 'requires_decision', $params);
    $this->addAdditionalFields();
  }

}
