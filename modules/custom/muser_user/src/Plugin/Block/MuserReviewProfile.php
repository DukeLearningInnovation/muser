<?php

namespace Drupal\muser_user\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MuserReviewProfile' block.
 *
 * @Block(
 *  id = "muser_review_profile_block",
 *  admin_label = @Translation("Muser review profile block"),
 * )
 */
class MuserReviewProfile extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * MuserCompleteProfile constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [
      '#cache' => [
        'contexts' => [
          'user',
        ],
        'tags' => [
          'current_round'
        ],
      ],
    ];

    $config = $this->configFactory->get('muser_system.settings');

    if ($this->currentUser->isAuthenticated() && $config->get('per_round_review_profile')) {

      $markup = '';
      $this->currentUser = User::load($this->currentUser->id());

      if (muser_user_profile_needs_review($this->currentUser)) {

        foreach (['student', 'mentor'] as $role) {
          if (!$this->currentUser->hasRole($role)) {
            continue;
          }
          if (!$message = $config->get('per_round_review_profile_text_' . $role)) {
            continue;
          }
          $markup = '<div class="review-profile">'
            . check_markup($message['value'], $message['format'])
            . "</div>\n";

          $this->currentUser->set('field_needs_review', FALSE);
          $this->currentUser->save();
          break;
        } // Loop thru roles.
      }

      if ($markup) {
        $build = [
          '#type' => 'markup',
          '#markup' => $markup,
          '#attached' => [
            'library' => ['muser_user/review-profile'],
          ],
          '#cache' => [
            'max-age' => 0,
          ],
        ];
      }

    } // Logged in?

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, $this->currentUser);

    return $build;

  }

}
