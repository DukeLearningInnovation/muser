<?php

namespace Drupal\muser_project\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'ApplicationReviewHeader' block.
 *
 * @Block(
 *  id = "muser_application_review_header_block",
 *  admin_label = @Translation("Muser Application review header block"),
 * )
 */
class ApplicationReviewHeader extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * @var integer
   */
  protected $current_round_nid;

  /**
   * ApplicationReviewHeader constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
        'contexts' => ['user'],
      ],
    ];
    if ($round_nid = muser_project_get_current_round()) {
      if ($round = Node::load($round_nid)) {
        $this->current_round_nid = $round_nid;
      }
    }

    if ($uid = $this->routeMatch->getParameter('user')) {
      $account = User::load($uid);
    }

    if (empty($round) || empty($account)) {
      return $build;
    }

    $build['dates'] = $round->field_accept_applications->get(0)->view('default');
    $build['dates']['#prefix'] = '<div class="field field--name-field-accept-applications field--type-daterange field--label-above">'
      . '<div class="field__label">'
      . $this->t('@title application-review period', ['@title' => $round->label()])
      . '</div><div class="field__item">';
    $build['dates']['#suffix'] = '</div></div>';

    if (!$counts = $this->getRequiresReviewText($account)) {
      $text = $this->t('You have no submitted applications for your Projects.');
    }
    else {
      $text = '';
      if ($info = $this->getCountText($counts, $account)) {
        if (!empty($info['text'])) {
          $text .= '<p>' . $info['text'] . '</p>';
        }
        if (!empty($info['todo'])) {
          $text .= '<p>' . $info['todo'] . '</p>';
        }
        if (!empty($info['downloads'])) {
          $text .= '<p>' . $info['downloads'] . '</p>';
        }
      }
    }

    if ($text) {
      $build['count'] = [
        '#type' => 'markup',
        '#markup' => '<div class="application-review-text">'
        . $text
        . '</div>',
      ];
    }

    $build['#cache'] = [
      'tags' => [
        'current_round',
        'node:' . $round_nid,
        'application_review_count:' . $account->id(),
      ],
      'contexts' => ['user'],
      'max-age' => muser_project_round_period_change_time('acceptance', $round_nid),
    ];

    return $build;

  }

  /**
   * Return count of application with different status values.
   *
   * @param \Drupal\user\Entity\User $account
   *   Account to report on.
   *
   * @return mixed
   *   Object with counts.
   */
  protected function getRequiresReviewText(User $account) {
    $query = \Drupal::database()->select('muser_applications_counts', 'mac');
    $query->addExpression('SUM(mac.submitted)', 'submitted');
    $query->addExpression('SUM(mac.pending)', 'pending');
    $query->addExpression('SUM(mac.in_review)', 'in_review');
    $query->addExpression('SUM(mac.accepted)', 'accepted');
    $query->addExpression('SUM(mac.rejected)', 'rejected');
    $query->addExpression('SUM(mac.no_decision)', 'no_decision');
    $query->condition('mac.project_uid', $account->id())
      ->condition('mac.round_nid', $this->current_round_nid);
    $count = $query->execute()->fetchObject();
    return $count;
  }

  /**
   * Return text describing counts.
   *
   * @param $counts object
   *   Array of count values.
   * @param \Drupal\user\Entity\User $account
   *   Mentor account.
   *
   * @return array
   *   Text to display.
   */
  protected function getCountText($counts, User $account) {

    if (empty($counts->submitted)) {
      return [];
    }

    $text = $this->formatPlural($counts->submitted,
      'You have 1 submitted application',
      'You have @count submitted applications')->__toString();
    $downloads_text = '';

    $types = [];
    $downloads = [];
    if (!empty($counts->pending)) {
      $types[] = Link::createFromRoute($this->t('@count pending', ['@count' => $counts->pending]), 'view.applications.page_new', ['user' => $account->id()])
        ->toString();
      $downloads[] = Link::createFromRoute($this->t('Download @count pending', ['@count' => $counts->pending]), 'muser_system.export_applications', ['stage' => 'pending'])
        ->toString();
    }
    if (!empty($counts->in_review)) {
      $types[] = Link::createFromRoute($this->t('@count in review', ['@count' => $counts->in_review]), 'view.applications.page_review', ['user' => $account->id()])
        ->toString();
      $downloads[] = Link::createFromRoute($this->t('Download @count in review', ['@count' => $counts->in_review]), 'muser_system.export_applications', ['stage' => 'in-review'])
        ->toString();
    }
    if (!empty($counts->accepted)) {
      $types[] = Link::createFromRoute($this->t('@count accepted', ['@count' => $counts->accepted]), 'view.applications.page_accepted', ['user' => $account->id()])
        ->toString();
    }
    if (!empty($counts->rejected)) {
      $types[] = Link::createFromRoute($this->t('@count rejected', ['@count' => $counts->rejected]), 'view.applications.page_rejected', ['user' => $account->id()])
        ->toString();
    }
    if ($types) {
      $text .= ' - ' . implode(', ', $types);
    }
    if ($downloads) {
      $downloads_text = '<i class="far fa-file-pdf"></i> ' . implode(', ', $downloads);
    }

    if (!empty($counts->no_decision)) {
      $todo = $this->formatPlural($counts->no_decision,
        'You still need to make a decision on (accept or reject) 1 application.',
        'You still need to make decisions on (accept or reject) @count applications.')
        ->__toString();
    }
    else {
      $todo = $this->t('You have completed your application review. <em>Well done!</em> Students will see a gold star next to your name for any projects that you post during the next round.')->__toString();
    }

    return ['text' => $text, 'todo' => $todo, 'downloads' => $downloads_text];

  }

}
