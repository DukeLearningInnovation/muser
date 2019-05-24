<?php

namespace Drupal\muser_system\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;

/**
 * Field handler to show if there are applications that require a decision.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("requires_decision")
 */
class RequiresDecisionSort extends SortPluginBase implements CacheableDependencyInterface {

  use UncacheableDependencyTrait;

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $this->query->addOrderBy(NULL, '(' . $this->tableAlias . '.no_decision > 0)', $this->options['order']);
  }

}
