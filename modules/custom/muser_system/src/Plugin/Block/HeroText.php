<?php

namespace Drupal\muser_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'HeroText' block.
 *
 * @Block(
 *  id = "muser_hero_text_block",
 *  admin_label = @Translation("Muser hero text block"),
 * )
 */
class HeroText extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * HeroText constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentUser = User::load($current_user->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];

    $round = NULL;
    if ($round_nid = muser_project_get_current_round()) {
      $round = Node::load($round_nid);
    }

    $markup = '';
    $config = $this->configFactory->get('muser_system.settings');

    foreach (['student', 'mentor'] as $role) {
      if (!$this->currentUser->hasRole($role)) {
        continue;
      }
      if (!$message = $config->get('hero_block_' . $role)) {
        continue;
      }
      $markup .= '<div class="role-message ' . Html::getClass('role-message--' . $role) . '">'
        . check_markup($message['value'], $message['format'])
        . "</div>\n";
    } // Loop thru roles.

    if (!$markup) {
      // No message yet, use anonymous message.
      if ($message = $config->get('hero_block_anonymous')) {
        $markup = '<div class="role-message role-message--anonymous">'
          . check_markup($message['value'], $message['format'])
          . "</div>\n";
      }
    } // Got any markup already?

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();
    $markup = $token_service->replace($markup, ['user' => $this->currentUser, 'muser' => ['round' => $round]]);

    $build[] = [
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => [
        'contexts' => [
          'user.roles',
          'user',
        ],
        'tags' => [
          'current_round',
          'node:' . $round_nid,
          'config:muser_system.settings',
        ],
      ],
    ];

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, $round);
    $renderer->addCacheableDependency($build, $this->currentUser);

    return $build;

  }

}
