<?php

namespace Drupal\muser_project\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'MyProjectsHeader' block.
 *
 * @Block(
 *  id = "muser_my_projects_header_block",
 *  admin_label = @Translation("Muser My projects header block"),
 * )
 */
class MyProjectsHeader extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * MyProjectsHeader constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    CurrentRouteMatch $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [
      '#cache' => [
        'tags' => ['current_round'],
        'contexts' => [
          'user.roles',
          'url.path',
        ],
      ],
    ];
    if ($round_nid = muser_project_get_current_round()) {
      $round = Node::load($round_nid);
    }

    if (empty($round)) {
      return $build;
    }

    $my_projects_url = '';
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name == 'view.projects.page') {
      $account = $this->currentUser;
      $my_projects_url = Url::fromRoute('view.my_projects.page', ['user' => $account->id()])
        ->toString();
    }
    else {
      if (!$uid = $this->routeMatch->getParameter('user')) {
        return $build;
      }
      $account = User::load($uid);
    } // On public Projects page?

    if (empty($account) || !$account->hasPermission('create project content')) {
      return $build;
    }

    if ($my_projects_url) {
      $build['my_projects'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('This is the public <em>Projects</em> page. Use the <a href="@url">My projects</a> page under "Mentor tasks" in the main menu above to manage your own projects.', ['@url' => $my_projects_url])
          ],
        ],
      ];
    } // Show link to My projects page?

    $build['dates'] = $round->field_post_projects->get(0)->view('default');
    $build['dates']['#prefix'] = '<div class="field field--name-field-post-projects field--type-daterange field--label-above">'
      . '<div class="field__label">'
      . $this->t('@title project-posting period', ['@title' => $round->label()])
      . '</div><div class="field__item">';
    $build['dates']['#suffix'] = '</div></div>';

    if (muser_project_round_in_period($round_nid, 'posting')) {
      $build['link'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('node.add', ['node_type' => 'project']),
        '#title' => $this->t('Create project'),
        '#attributes' => ['class' => 'button--standard'],
      ];
    } // In posting period?

    $build['#cache'] = [
      'tags' => ['current_round', 'node:' . $round_nid],
      'contexts' => [
        'user',
        'url.path',
      ],
      'max-age' => muser_project_round_period_change_time('posting', $round_nid),
    ];

    return $build;

  }

}
