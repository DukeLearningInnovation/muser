<?php

namespace Drupal\muser_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MuserCompleteProfile' block.
 *
 * @Block(
 *  id = "muser_complete_profile_block",
 *  admin_label = @Translation("Muser complete profile block"),
 * )
 */
class MuserCompleteProfile extends BlockBase implements ContainerFactoryPluginInterface {

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('current_user')
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
      ],
    ];

    if ($this->currentUser->isAuthenticated()) {

      $markup = '';
      $this->currentUser = User::load($this->currentUser->id());
      $roles = array_combine($this->currentUser->getRoles(), $this->currentUser->getRoles());
      unset($roles['authenticated']);

      if (!muser_user_profile_is_complete($this->currentUser)) {

        $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()])
          ->toString();

        $markup = '<div class="complete-profile">'
          . $this->t('Please complete your <a href="@url">user profile</a> so you can access all the features of this site.', ['@url' => $url])
          . '</div>';

      }
      elseif (!$roles) {
        $markup = '<div class="complete-profile complete-profile-waiting">'
          . $this->t("Once your request for access to the site is approved, you'll be able to use all features.")
          . '</div>';
      } // User type set (profile updated)? / got any roles?

      if ($markup) {
        $build = [
          '#markup' => $markup,
          '#cache' => [
            'contexts' => [
              'user.roles',
              'user',
            ],
          ],
        ];
      }

    } // Logged in?

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, $this->currentUser);

    return $build;

  }

}
