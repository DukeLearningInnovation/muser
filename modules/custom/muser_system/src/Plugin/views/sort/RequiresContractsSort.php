<?php

namespace Drupal\muser_system\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;

/**
 * Field handler to show if there are applications that require contracts.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("requires_contracts")
 */
class RequiresContractsSort extends SortPluginBase implements CacheableDependencyInterface {

  use UncacheableDependencyTrait;

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $this->query->addOrderBy(NULL, '(' . $this->tableAlias . '.contract_required_mentor > 0)', $this->options['order']);
  }

}
