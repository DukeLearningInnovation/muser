<?php

namespace Drupal\muser_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\flag\Entity\Flagging;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Controller for basic Muser pages.
 */
class BasicController extends ControllerBase {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * BasicController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Show essay guidelines.
   *
   * @return array
   *   Build array.
   */
  public function essayGuidelines() {

    $build = [
      '#cache' => [
        'tags' => ['config:muser_system.settings'],
      ],
    ];

    $config = $this->configFactory->get('muser_system.settings');

    if (!$message = $config->get('application_essay_guidelines')) {
      return $build;
    }

    $markup = check_markup($message['value'], $message['format']);

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();
    $markup = $token_service->replace($markup);

    $build[] = [
      '#type' => 'markup',
      '#markup' => '<div class="essay-guidelines__text">'
      . $markup
      . '</div>',
      '#cache' => [
        'tags' => ['config:muser_system.settings'],
      ],
    ];

    return $build;

  }

  /**
   * @param string $config_key
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function configItemWithTokens(string $config_key) {

    $value = $this->requestStack->getCurrentRequest()->request->get('value');
    if (!$format = $this->requestStack->getCurrentRequest()->request->get('format')) {
      $format = 'raw';
    }

    switch ($config_key) {
      case 'mentor_request_email_body':
      case 'mentor_grant_email_body':
      case 'new_project_email_body':
      case 'post_projects_start_email_body':
      case 'post_projects_end_email_body':
      case 'review_applications_start_email_body':
      case 'review_applications_end_email_body':
      case 'after_round_email_body':
      case 'student_accepted_email_body':
      case 'student_rejected_email_body':
      case 'contract_reminder_mentor_start_email_body':
      case 'contract_reminder_mentor_end_email_body':
      case 'contract_reminder_student_start_email_body':
      case 'contract_reminder_student_end_email_body':
        // Valid configuration setting to preview-- do nothing.
        break;
      default:
        return new Response($this->t('Invalid configuration setting.'));
    }

    // Load sample data.
    $node = $this->getSampleProject();
    $account = $node->getOwner();
    $muser_data['project'] = $node;
    $muser_data['round'] = muser_project_get_current_round(TRUE);
    $muser_data['flagging'] = $this->getSampleFlagging();

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();

    $text = $token_service->replace($value, [
      'user' => $account,
      'node' => $node,
      'muser' => $muser_data,
    ]);

    if ($format == 'raw') {
      $text = nl2br(htmlentities($text, NULL, NULL, FALSE));
    }
    else {
      $text = check_markup($text, $format);
    }

    return new Response($text);

  }

  private function getSampleProject() {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'project')
      ->condition('uid', 1, '<>')
      ->sort('created', 'DESC')
      ->execute();
    $nid = ($nids) ? reset($nids) : NULL;
    if ($nid) {
      return Node::load($nid);
    }
    return NULL;
  }

  private function getSampleFlagging() {
    $query = \Drupal::database()->select('muser_applications', 'ma');
    $query->addField('ma', 'fid');
    $query->condition('ma.is_submitted', 1);
    $query->orderBy('ma.fid', 'DESC');
    if ($fid = $query->execute()->fetchField()) {
      return Flagging::load($fid);
    }
    return NULL;
  }

  /**
   * Show reports landing page.
   *
   * @return array
   *   Build array.
   */
  public function reports() {

    $reports = [
      [
        'title' => $this->t('Applications'),
        'view' => 'administer_applications',
        'display' => 'page_1',
        'export_display' => 'export',
      ],
      [
        'title' => $this->t('Application counts'),
        'view' => 'application_counts',
        'display' => 'page_1',
        'export_display' => 'export',
      ],
    ];

    $report_links = [];
    foreach ($reports as $report) {
      $report_links[] = [
        'link' => Link::createFromRoute($report['title'], 'view.' . $report['view'] . '.' . $report['display'])->toString(),
        'export' => Link::createFromRoute($this->t('Download CSV'), 'view.' . $report['view'] . '.' . $report['export_display'])->toString(),
      ];
    } // Loop thru reports.

    $round_links = [];

    $view = Views::getView('rounds');
    $view->setDisplay('page_1');
    $view->setCurrentPage(0);
    $view->render();
    if ($view->result) {
      foreach ($view->result as $row) {
        $round = [
          'name' => $row->_entity->label(),
          'links' => [],
        ];
        if ($row->_entity->field_is_current->value) {
          $round['name'] .= ' (' . $this->t('Current') . ')';
        }

        $options = [
          'round' => $row->_entity->id(),
        ];
        foreach ($reports as $report) {
          $round['links'][] = [
            'link' => Link::createFromRoute($report['title'], 'view.' . $report['view'] . '.' . $report['display'], $options)->toString(),
            'export' => Link::createFromRoute($this->t('CSV'), 'view.' . $report['view'] . '.' . $report['export_display'], $options)->toString(),
          ];
        } // Loop thru reports.

        $round_links[] = $round;

      } // Loop thru rows.
    } // Got rounds>

    return [
      '#theme' => 'muser_reports',
      '#report_links' => $report_links,
      '#round_links' => $round_links,
    ];

  }

}
