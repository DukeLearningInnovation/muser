<?php

namespace Drupal\muser_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides a 'RoundInfo' block.
 *
 * @Block(
 *  id = "muser_round_info_block",
 *  admin_label = @Translation("Muser round info block"),
 * )
 */
class RoundInfo extends BlockBase implements ContainerFactoryPluginInterface {

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
        'tags' => ['current_round', 'user.roles'],
      ],
    ];
    if ($round_nid = muser_project_get_current_round()) {
      $round = Node::load($round_nid);
    }

    if (empty($round)) {
      if ($this->currentUser->hasPermission('create round content')) {
        $manage_url = Url::fromRoute('view.rounds.page_1')->toString();
        $create_url = Url::fromRoute('node.add', ['node_type' => 'round'])->toString();
        $build['my_projects'] = [
          '#theme' => 'status_messages',
          '#message_list' => [
            'warning' => [
              $this->t('There is no <em>current Round</em> set. You may <a href="@manage_url">manage Rounds</a> or <a href="@create_url">create a new Round</a>.', [
                '@manage_url' => $manage_url,
                '@create_url' => $create_url,
              ]),
            ],
          ],
          '#cache' => [
            'tags' => ['current_round', 'user.roles'],
          ],
        ];
      }
      return $build;
    }

    if (_muser_system_contracts_enabled(FALSE)) {
      if ($this->currentUser->hasPermission('create round content')) {
        if (!$round->get('field_sign_contracts')->get(0)) {
          $update_url = Url::fromRoute('entity.node.edit_form', ['node' => $round->id()])
            ->toString();
          $build['contracts_error'] = [
            '#theme' => 'status_messages',
            '#message_list' => [
              'error' => [
                $this->t('Contracts are enabled, but contract dates are not set for the current Round. You should <a href="@update_url">update the current Round</a> to set the contract dates.', [
                  '@update_url' => $update_url,
                ]),
              ],
            ],
            '#cache' => [
              'tags' => [
                'node:' . $round_nid,
                'current_round',
                'user.roles',
              ],
            ],
          ];
        } // Contract dates set?
      } // Can they manage rounds?
    } // Contracts enabled?

    $build[] = [
      '#theme' => 'muser_round_info',
      '#title' => $this->t('Timeline for @title', ['@title' => $round->label()]),
      '#dates' => $this->getDates($round),
      '#mentor_title' => $this->t('For Research Mentors'),
      '#student_title' => $this->t('For Students'),
      '#show_contract_dates' => _muser_system_contracts_enabled(),
      '#cache' => [
        'tags' => [
          'node:' . $round_nid,
          'current_round',
          'user.roles',
        ],
      ],
    ];

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, $round);

    return $build;

  }

  /**
   * @param \Drupal\node\Entity\Node $round
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getDates(Node $round) {

    $fields = [
      'field_accept_applications' => [
        'start' => $this->t('First day to accept applications'),
        'end' => $this->t('Last day to accept applications'),
      ],
      'field_post_projects' => [
        'start' => $this->t('First day to post projects'),
        'end' => $this->t('Last day to post projects'),
      ],
      'field_apply' => [
        'start' => $this->t('First day to apply for projects'),
        'end' => $this->t('Last day to apply for projects'),
      ],
      'field_sign_contracts' => [
        'start' => $this->t('First day to agree to contracts'),
        'end' => $this->t('Last day to agree to contracts'),
      ],
    ];

    $dates = [];
    $user_tz = new \DateTimeZone(date_default_timezone_get());
    $now = new DrupalDateTime();

    foreach ($fields as $field => $text) {
      if (!$date_field = $round->get($field)->get(0)) {
        continue;
      }
      $values = $date_field->getValue();
      foreach (['value', 'end_value'] as $type) {
        $date = new DrupalDateTime($values[$type], DateTimeItemInterface::STORAGE_TIMEZONE);
        $date->setTimezone($user_tz);
        $diff = $date->diff($now);
        $key = ($type == 'value') ? 'start' : 'end';
        $dates[$field][$key] = [
          'date' => $date->format('l, F j \a\t g:ia'),
          'future' => $diff->invert,
          'class' => ($diff->invert) ? 'future' : 'past',
          'text' => $text[$key],
        ];
      } // Alternate start/end.
    } // Loop thru different dates.

    return $dates;

  }

}
