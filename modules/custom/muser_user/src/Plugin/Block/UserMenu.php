<?php

namespace Drupal\muser_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\RedirectDestination;

/**
 * Provides a 'UserMenu' block.
 *
 * @Block(
 *  id = "muser_user_menu",
 *  admin_label = @Translation("Muser user menu block"),
 * )
 */
class UserMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * @var RedirectDestination
   */
  private $redirectDestination;

  /**
   * UserMenu constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Routing\RedirectDestination $redirect_destination
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    RedirectDestination $redirect_destination
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->redirectDestination = $redirect_destination;
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
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $menu_items = [];

    if ($this->currentUser->isAnonymous()) {

      // Anonymous user.
      $user_login_method = $this->configFactory->get('muser_system.settings')->get('user_login_method');

      switch ($user_login_method) {

        case 'basicshib':
          // When using Shib for login, use a Shib login link.
          /** @var \Drupal\basicshib\AuthenticationHandler $authentication_handler */
          $authentication_handler = \Drupal::service('basicshib.authentication_handler');
          $menu_items['profile'] = [
            'link' => [
              '#theme' => 'basicshib_login_link',
              '#login_url' => $authentication_handler->getLoginUrl(),
              '#login_link_label' => $this->t('Log in'),
              '#cache' => [
                'contexts' => ['url.path', 'user'],
              ],
            ],
            'classes' => 'role--anonymous',
          ];
          break;

        case 'drupal':
        default:
          $menu_items['profile'] = [
            'link' => [
              '#type' => 'link',
              '#url' => Url::fromRoute('user.login', [$this->redirectDestination->getAsArray()]),
              '#title' => $this->t('Log in'),
              '#cache' => [
                'contexts' => ['url.path', 'user'],
              ],
            ],
            'classes' => 'role--anonymous',
          ];

      } // End switch on login method.

    }
    else {

      // Authenticated user.
      $this->currentUser = User::load($this->currentUser->id());
      $current_uri = $this->requestStack->getCurrentRequest()->getRequestUri();

      // Mentor menu items.
      if ($this->currentUser->hasRole('mentor')) {
        $options = [];
        $menu_item_classes = ['menu--mentor'];
        if (strpos($current_uri, '/mentor') === 0) {
          $options = ['attributes' => ['class' => 'is-active']];
          $menu_item_classes[] = 'menu-item--active-trail';
        }
        $menu_items['mentor_tasks'] = [
          'link' => [
            '#type' => 'link',
            '#url' => Url::fromRoute('view.my_projects.page', ['user' => $this->currentUser->id()], $options),
            '#title' => $this->t('Mentor tasks'),
          ],
          'classes' => implode(' ', $menu_item_classes),
        ];
        $options = ['set_active_class' => TRUE];
        $menu_items['mentor_tasks']['children'][] = Link::createFromRoute($this->t('My projects'), 'view.my_projects.page', ['user' => $this->currentUser->id()], $options);
        $menu_items['mentor_tasks']['children'][] = Link::createFromRoute($this->t('Applications'), 'view.applications.page_new', ['user' => $this->currentUser->id()], $options);
      } // Mentor?

      // Student menu item(s).
      if ($this->currentUser->hasRole('student')) {
        $options = [];
        $menu_item_classes = ['menu--student'];
        if (strpos($current_uri, '/student') === 0) {
          $options = ['attributes' => ['class' => 'is-active']];
          $menu_item_classes[] = 'menu-item--active-trail';
        }
        $menu_items['student'] = [
          'link' => [
            '#type' => 'link',
            '#url' => Url::fromRoute('view.my_favorites.page', ['user' => $this->currentUser->id()], $options),
            '#title' => $this->t('My applications'),
          ],
          'classes' => implode(' ', $menu_item_classes),
        ];
      } // Student?

      $options = [];
      $menu_item_classes = [];
      if (strpos($current_uri, '/user') === 0) {
        $options = ['attributes' => ['class' => 'is-active']];
        $menu_item_classes[] = 'menu-item--active-trail';
      }

      if (!muser_user_profile_is_complete($this->currentUser)) {
        $menu_item_classes[] = 'user-profile--incomplete';
      }

      $roles = $this->currentUser->getRoles();
      foreach ($roles as $role) {
        $menu_item_classes[] = Html::getClass('role--' . $role);
      }

      $menu_items['profile'] = [
        'link' => [
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()], $options),
          '#title' => $this->currentUser->getDisplayName(),
        ],
        'classes' => implode(' ', $menu_item_classes),
      ];

      $options = ['set_active_class' => TRUE];
      $menu_items['profile']['children'][] = Link::createFromRoute($this->t('My profile'), 'entity.user.canonical', ['user' => $this->currentUser->id()], $options)->toString();
      $menu_items['profile']['children'][] = Link::createFromRoute($this->t('Edit profile'), 'entity.user.edit_form', ['user' => $this->currentUser->id()], $options)->toString();
      $menu_items['profile']['children'][] = Link::createFromRoute($this->t('Log out'), 'user.logout')->toString();

    } // Logged in?

    $build = [
      '#theme' => 'muser_user_menu',
      '#nav' => [
        'id' => 'block-muserusermenu',
        'label' => $this->t('Muser navigation'),
        'label_id' => 'block-muserusermenu-menu',
      ],
      '#menu_items' => $menu_items,
      '#cache' => [
        'contexts' => [
          'user.roles',
          'user',
          'url.path',
        ],
      ],
    ];

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, $this->currentUser);

    return $build;

  }

}
