<?php

namespace Drupal\muser_project\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;
use Drupal\views\Views;

/**
 * Filter handler to give access to project creator plus additional mentors.
 *
 * @ViewsArgument("allowed_mentor")
 */
class AllowedMentor extends Uid {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {

    $this->ensureMyTable();
    $placeholder = $this->placeholder();

    $configuration = [
      'type' => 'LEFT',
      'table' => 'node__field_additional_mentors',
      'field' => 'entity_id',
      'left_table' => $this->tableAlias,
      'left_field' => 'nid',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node__field_additional_mentors', $join, $this->tableAlias);

    $this->query->addWhereExpression(0,
      "$this->tableAlias.uid = $placeholder
      OR node__field_additional_mentors.field_additional_mentors_target_id = $placeholder",
     [$placeholder => $this->argument]);

  }

}
