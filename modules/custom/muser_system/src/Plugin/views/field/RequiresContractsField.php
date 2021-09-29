<?php

namespace Drupal\muser_system\Plugin\views\field;

use Drupal\views\Plugin\views\field\Boolean;

/**
 * Field handler to show if there are applications that require contracts.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("requires_contracts")
 */
class RequiresContractsField extends Boolean {

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField(NULL, '(' . $this->tableAlias . '.contract_required_mentor > 0)', 'requires_contracts', $params);
    $this->addAdditionalFields();
  }

}
