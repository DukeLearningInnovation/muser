<?php

namespace Drupal\muser_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'CopyrightMessage' block.
 *
 * @Block(
 *  id = "muser_copyright_message",
 *  admin_label = @Translation("Muser copyright message block"),
 * )
 */
class CopyrightMessage extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CopyrightMessage constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];

    $now = new DrupalDateTime();
    $year = $now->format('Y');

    $message = '<span class="copyright-date">&copy; ' . $year . '</span>';

    if ($text = $this->configFactory->get('muser_system.settings')->get('copyright_message')) {
      $message .= ' <span class="copyright-text">' . $text . '</span>';
    }

    // Want to cache this until the end of the current year.
    $end = new DrupalDateTime($year . '-12-31 11:59:59');
    $max_age = $end->getTimestamp() - $now->getTimestamp();

    $build[] = [
      '#type' => 'markup',
      '#markup' => $message,
      '#cache' => [
        'max-age' => $max_age,
        'tags' => ['config:muser_system.settings'],
      ],
    ];

    return $build;

  }

}
