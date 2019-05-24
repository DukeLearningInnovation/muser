<?php

namespace Drupal\muser_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'UserApplicationCount' block.
 *
 * @Block(
 *  id = "muser_application_count",
 *  admin_label = @Translation("Muser application count block"),
 * )
 */
class UserApplicationCount extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * UserApplicationCount constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $route_match,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
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
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [
      '#cache' => [
        'tags' => ['current_round'],
      ],
    ];

    if ($uid = $this->routeMatch->getParameter('user')) {
      $account = User::load($uid);
    }
    elseif ($this->routeMatch->getRouteName() == 'view.projects.page') {
      // Projects page-- use current user.
      $account = $this->currentUser;
    }

    if (!empty($account) && $account->isAuthenticated()) {

      if ($round_nid = muser_project_get_current_round()) {
        $round = Node::load($round_nid);
      }
      if (empty($round)) {
        return $build;
      }

      // Figure out if we're before, in, or after the application period.
      $field_data = $round->get('field_apply')->get(0)->getValue();
      $begining = new DrupalDateTime($field_data['value'], DateTimeItemInterface::STORAGE_TIMEZONE);
      $end = new DrupalDateTime($field_data['end_value'], DateTimeItemInterface::STORAGE_TIMEZONE);
      $now = new DrupalDateTime();
      $b = $begining->diff($now);
      $e = $end->diff($now);
      if (!$b->invert && $e->invert) {
        $period_status = 'current';
      }
      elseif ($b->invert) {
        $period_status = 'future';
      }
      else {
        $period_status = 'past';
      }

      // "You have submitted X of your Y allowed applications".
      $submitted_num = muser_project_get_user_application_count($account->id());
      if ($allowed_num = $round->field_num_app_per_student->value) {
        // Have a limit.
        if ($allowed_num == 1) {
          if ($submitted_num) {
            if ($period_status == 'past') {
              $submitted = $this->t('You submitted your application.');
            }
            else {
              $submitted = $this->t('You have submitted your application.');
            }
          }
          else {
            if ($period_status == 'past') {
              $submitted = $this->t('You did not submit an application.');
            }
            else {
              $submitted = $this->t('You have not submitted an application.');
            }
          }
        }
        else {
          if ($period_status == 'past') {
            $submitted = $this->t('You submitted %submitted of your %allowed allowed applications.', [
              '%submitted' => $submitted_num,
              '%allowed' => $allowed_num,
            ]);
          }
          else {
            $submitted = $this->t('You have submitted %submitted of your %allowed allowed applications.', [
              '%submitted' => $submitted_num,
              '%allowed' => $allowed_num,
            ]);
          }
        }
      }
      else {
        // No limit set.
        if ($period_status == 'past') {
          $submitted = $this->formatPlural($submitted_num,
            'You submitted 1 application.',
            'You submitted %count applications.',
            ['%count' => $submitted_num]);
        }
        else {
          $submitted = $this->formatPlural($submitted_num,
            'You have submitted 1 application.',
            'You have submitted %count applications.',
            ['%count' => $submitted_num]);
        }
      } // Got a limit on number of applications?

      $build['dates'] = $round->field_apply->get(0)->view('default');

      if ($period_status == 'current') {
        $text = $this->t('@title application period', ['@title' => $round->label()]);
      }
      elseif ($period_status == 'future') {
        $text = $this->t('@title application period has not started yet', ['@title' => $round->label()]);
      }
      else {
        $text = $this->t('@title application period has ended', ['@title' => $round->label()]);
      }

      $build['dates']['#prefix'] = '<div class="field field--name-field-apply field--type-daterange field--label-above">'
        . '<div class="field__label">'
        . $text
        . '</div><div class="field__item">';
      $build['dates']['#suffix'] = '</div></div>';

      if ($period_status != 'future') {
        $build['count'] = [
          '#markup' => '<div class="submitted-text">'
          . $submitted
          . '</div>',
        ];
      }

      $build['#cache'] = [
        'contexts' => ['user'],
        'max-age' => muser_project_round_period_change_time('application', $round_nid),
        'tags' => [
          'current_round',
          'node:' . $round_nid,
          'user:' . $account->id(),
          'application_count:' . $account->id(),
        ],
      ];

    } // Logged in?

    return $build;

  }

}
