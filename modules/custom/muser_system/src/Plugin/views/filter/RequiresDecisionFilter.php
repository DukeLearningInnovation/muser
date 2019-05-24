<?php

namespace Drupal\muser_system\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Field handler to show if there are applications that require a decision.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("requires_decision")
 */
class RequiresDecisionFilter extends BooleanOperator {

  /**
   * Return the possible options for this filter.
   *
   * Child classes should override this function to set the possible values
   * for the filter.  Since this is a boolean filter, the array should have
   * two possible keys: 1 for "True" and 0 for "False", although the labels
   * can be whatever makes sense for the filter.  These values are used for
   * configuring the filter, when the filter is exposed, and in the admin
   * summary of the filter.  Normally, this should be static data, but if it's
   * dynamic for some reason, child classes should use a guard to reduce
   * database hits as much as possible.
   */
  public function getValueOptions() {
    $this->valueOptions = [1 => $this->t('Yes'), 0 => $this->t('No')];
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    if (!empty($this->value)) {
      $where = '(' . $this->tableAlias . '.no_decision > 0)';
    }
    else {
      $where = '(' . $this->tableAlias . '.no_decision = 0)';
    }
    $this->query->addWhereExpression($this->options['group'], $where);
  }

}
