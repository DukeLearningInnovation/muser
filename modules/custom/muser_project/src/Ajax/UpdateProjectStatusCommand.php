<?php

namespace Drupal\muser_project\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class UpdateProjectStatusCommand implements CommandInterface {

  /**
   * Identifies the action link to be flashed.
   *
   * @var string
   */
  protected $selector;

  /**
   * The class to apply.
   *
   * @var string
   */
  protected $class;

  /**
   * Construct a message Flasher.
   *
   * @param string $selector
   *   Identifies the action link to be flashed.
   * @param string $class
   *   The class to change.
   */
  public function __construct($selector, $class) {
    $this->selector = $selector;
    $this->class = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateProjectStatus',
      'selector' => $this->selector,
      'class' => $this->class,
    ];
  }

}
