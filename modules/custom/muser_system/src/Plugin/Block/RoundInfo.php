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
        'tags' => ['current_round'],
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
              $this->t('There is no <em>current Round</em> set. You may <a href="@manage_url">manage Rounds</a> or <a href="@create_url">create a new Round</a>.', ['@manage_url' => $manage_url, '@create_url' => $create_url])
            ],
          ],
          '#cache' => [
            'tags' => ['current_round'],
          ],
        ];
      }
      return $build;
    }

    $build[] = [
      '#theme' => 'muser_round_info',
      '#title' => $this->t('Timeline for @title', ['@title' => $round->label()]),
      '#dates' => $this->getDates($round),
      '#mentor_title' => $this->t('For Research Mentors'),
      '#student_title' => $this->t('For Students'),
      '#cache' => [
        'tags' => [
          'node:' . $round_nid,
          'current_round',
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
    $user_tz = new \DateTimeZone(drupal_get_user_timezone());
    $now = new DrupalDateTime();

    foreach ($fields as $field => $text) {
      $values = $round->get($field)->get(0)->getValue();
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
