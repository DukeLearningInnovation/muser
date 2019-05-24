<?php

namespace Drupal\muser_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\muser_system\ScheduledEmails;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\Time;

/**
 * Controller for running scheduled tasks.
 */
class ScheduledTasksController extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected $dateTime;

  /**
   * ScheduledTasksController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Datetime\Time $date_time
   */
  public function __construct(ConfigFactoryInterface $config_factory, Time $date_time) {
    $this->configFactory = $config_factory;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * Run scheduled emailer.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function sendEmails() {
    $config = $this->configFactory->get('muser_system.settings');
    $mailer = new ScheduledEmails($config, $this->state(), $this->dateTime);
    $mailer->sendEmails();
    return new Response('', 204);
  }

  /**
   * Run scheduled emailer and redirect.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to System Status Report page.
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function sendEmailsManually() {
    $config = $this->configFactory->get('muser_system.settings');
    $mailer = new ScheduledEmails($config, $this->state(), $this->dateTime);
    $mailer->sendEmails();
    return $this->redirect('system.status');
  }

  /**
   * Run round checker.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   */
  public function setCurrentRound() {
    muser_system_set_current_round();
    return new Response('', 204);
  }

  /**
   * Run round checker and redirect.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to System Status Report page.
   */
  public function setCurrentRoundManually() {
    if ($new_round = muser_system_set_current_round()) {
      $this->messenger()->addStatus($this->t('Current round changed to %title (nid: @nid).', ['%title' => $new_round->label(), '@nid' => $new_round->id()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Current round not changed.'));
    }
    return $this->redirect('system.status');
  }

}
