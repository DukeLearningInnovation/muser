<?php

namespace Drupal\muser_system;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\flag\Entity\Flagging;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Datetime\Time;

/**
 * Controller for running scheduled tasks.
 */
class ScheduledEmails {

  /**
   * Current round node.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $round;

  /**
   * Configuration for emails to send out.
   *
   * @var array
   */
  protected $emailTypes = [
    'post_projects_start' => [
      'field' => 'field_post_projects_start_email',
      'date_field' => 'field_post_projects',
      'key' => 'value',
    ],
    'post_projects_end' => [
      'field' => 'field_post_projects_end_email',
      'date_field' => 'field_post_projects',
      'key' => 'end_value',
    ],
    'review_applications_start' => [
      'field' => 'field_review_apps_start_email',
      'date_field' => 'field_accept_applications',
      'key' => 'value',
    ],
    'review_applications_end' => [
      'field' => 'field_review_apps_end_email',
      'date_field' => 'field_accept_applications',
      'key' => 'end_value',
    ],
    'after_round' => [
      'field' => 'field_after_round_email',
      'date_field' => 'field_accept_applications',
      'key' => 'end_value',
    ],
    'student_accepted' => [
      'field' => 'field_accepted_student_email',
      'date_field' => 'field_accept_applications',
      'key' => 'end_value',
    ],
    'student_rejected' => [
      'field' => 'field_rejected_student_email',
      'date_field' => 'field_accept_applications',
      'key' => 'end_value',
    ],
  ];

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var Time
   */
  protected $dateTime;

  /**
   * ScheduledEmails constructor.
   *
   * @param $config
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Component\Datetime\Time $date_time
   * @param \Drupal\node\Entity\Node|NULL $round
   */
  public function __construct($config, StateInterface $state, Time $date_time, Node $round = NULL) {
    $this->config = $config;
    $this->state = $state;
    $this->dateTime = $date_time;
    if (!$round) {
      if ($nid = muser_project_get_current_round()) {
        $this->round = Node::load($nid);
      }
    }
    else {
      $this->round = $round;
    }
  }

  /**
   * Send all scheduled emails.
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function sendEmails() {

    if (!$this->round) {
      return FALSE;
    }

    foreach ($this->emailTypes as $type => $info) {

      if ($this->round->{$info['field']}->value) {
        // Already sent.
        continue;
      }

      $field_data = $this->round->get($info['date_field'])->get(0)->getValue();

      if ($offset = $this->config->get($type . '_email_offset')) {
        $offset = muser_system_reverse_time_offset($offset);
        if (strtotime($offset) === FALSE) {
          // Offset is invalid.
          \Drupal::logger('muser_system')->error('Invalid offset "@offset" for @type email.', ['@offset' => $offset, '@type' => $type]);
          continue;
        }
      }
      else {
        $offset = 'now';
      } // Got an offset?

      $current_time = new DrupalDateTime($offset);
      $round_date = new DrupalDateTime($field_data[$info['key']], DateTimeItemInterface::STORAGE_TIMEZONE);
      $diff = $round_date->diff($current_time);

      $done = FALSE;

      if (!$diff->invert) {

        // In past, need to send mail.
        \Drupal::logger('muser_system')->info('Scheduled @type email must be processed.', ['@type' => $type]);

        // Allow global override of scheduled email sending.
        if (!empty($GLOBALS['do_not_send_scheduled_emails'])) {
          \Drupal::logger('muser_system')->info('Scheduled @type email sending prevented by a settings override.', ['@type' => $type]);
          $done = TRUE;
        }
        else {

          switch ($type) {

            case 'after_round':
            case 'student_accepted':
            case 'student_rejected':
              $done = $this->processStudentEmail($type);
              break;

            default:
              $done = $this->processMentorEmail($type);
          }

        } // Prevent email sending?

        if ($done) {
          $this->round->{$info['field']}->value = 1;
          $this->round->save();
        }

      }

    } // Loop thru possible emails.

    $this->state->set('muser_system.scheduled_emails_checked', $this->dateTime->getRequestTime());

    return $done;

  }

  /**
   * @param $type
   *
   * @return bool
   */
  protected function processStudentEmail($type) {

    \Drupal::logger('muser_system')->info('Processing student email type "@type".', ['@type' => $type]);

    switch ($type) {
      case 'after_round':
        $results = $this->getParticipatingStudents();
        break;

      case 'student_accepted':
        $results = $this->getApplicantStudents('accepted');
        break;

      case 'student_rejected':
        $results = $this->getApplicantStudents('rejected');
        break;

      default:
        return FALSE;
    }

    $count = 0;
    foreach ($results as $row) {
      $account = User::load($row->uid);
      if (!empty($row->fid)) {
        $flagging = Flagging::load($row->fid);
      }
      else {
        $flagging = NULL;
      }
      $this->sendScheduledEmail($type, $account, $flagging);
      $count++;
    } // Loop thru students.

    \Drupal::logger('muser_system')->info('Processed student email type "@type". Emails to send: @count', ['@type' => $type, '@count' => $count]);

    // @todo - what to return here?
    return TRUE;

  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getParticipatingStudents() {
    // Get all students who have participated in this round.
    // Check for opt-outs here.
    // @todo - Get all students who have favorited anything? Or just submitted?
    $query = \Drupal::database()->select('muser_applications', 'ma');
    $query->addField('ma', 'application_uid', 'uid');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ma.application_uid');
    $query->join('user__roles', 'ur', "ur.entity_id = ma.application_uid AND ur.bundle = 'user'");
    $query->join('user__field_do_not_send_emails', 'no_send', "no_send.entity_id = ma.application_uid AND no_send.bundle = 'user'");
    $query->condition('ur.roles_target_id', 'student')
      ->condition('ufd.status', 1)
      ->condition('ma.round_nid', $this->round->id())
      ->condition('no_send.field_do_not_send_emails_value', 1, '<>');
    $results = $query->distinct()->execute();
    return $results;
  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getApplicantStudents($status) {
    // Get all students accepted or rejected students for this round.
    $query = \Drupal::database()->select('muser_applications', 'ma');
    $query->addField('ma', 'application_uid', 'uid');
    $query->addField('ma', 'fid');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ma.application_uid');
    $query->join('user__roles', 'ur', "ur.entity_id = ma.application_uid AND ur.bundle = 'user'");
    $query->condition('ur.roles_target_id', 'student')
      ->condition('ufd.status', 1)
      ->condition('ma.is_submitted', 1)
      ->condition('ma.round_nid', $this->round->id());
    if ($status == 'accepted') {
      $query->condition('ma.status', 'accepted');
    }
    else {
      $query->condition('ma.status', 'accepted', '<>');
    }
    $query->orderBy('ma.fid');
    $results = $query->distinct()->execute();
    return $results;
  }

  /**
   * @param $type
   *
   * @return bool
   */
  protected function processMentorEmail($type) {

    \Drupal::logger('muser_system')->info('Processing mentor email type "@type".', ['@type' => $type]);

    switch ($type) {

      case 'post_projects_start':
      case 'post_projects_end':
        $results = $this->getAllMentors();
        break;

      case 'review_applications_start':
      case 'review_applications_end':
        $results = $this->getMentorsWithApplicationsToReview();
        break;

      default:
        return FALSE;
    }

    $count = 0;
    foreach ($results as $row) {
      $account = User::load($row->uid);
      $this->sendScheduledEmail($type, $account);
      $count++;
    } // Loop thru mentors.

    \Drupal::logger('muser_system')->info('Processed mentor email type "@type". Emails to send: @count', ['@type' => $type, '@count' => $count]);

    // @todo - what to return here?
    return TRUE;

  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getAllMentors() {
    // Get all users with "mentor" role.
    // Check for opt-outs here.
    $query = \Drupal::database()->select('users_field_data', 'ufd');
    $query->addField('ufd', 'uid');
    $query->join('user__roles', 'ur', "ur.entity_id = ufd.uid AND ur.bundle = 'user'");
    $query->join('user__field_do_not_send_emails', 'no_send', "no_send.entity_id = ufd.uid AND no_send.bundle = 'user'");
    $query->condition('ur.roles_target_id', 'mentor')
      ->condition('ufd.status', 1)
      ->condition('no_send.field_do_not_send_emails_value', 1, '<>');
    $results = $query->distinct()->execute();
    return $results;
  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getMentorsWithApplicationsToReview() {
    // Get all mentors with applications requiring review.
    // Do not check for opt-outs-- always send.
    $query = \Drupal::database()->select('muser_applications_counts', 'mac');
    $query->addField('mac', 'project_uid', 'uid');
    $query->join('node_field_data', 'nfd', 'nfd.nid = mac.project_nid');
    $query->join('users_field_data', 'ufd', 'ufd.uid = mac.project_uid');
    $query->condition('nfd.status', 1)
      ->condition('ufd.status', 1)
      ->condition('mac.round_nid', $this->round->id())
      ->condition('mac.no_decision', 0, '>');
    $results = $query->distinct()->execute();
    return $results;
  }

  /**
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getMentorsWithProjectsInRound() {
    // Get all mentors who have participated in this round.
    // Do not check for opt-outs-- always send.
    $query = \Drupal::database()->select('node_field_data', 'fd');
    $query->addField('fd', 'uid');
    $query->join('node__field_project', 'fp', 'fp.field_project_target_id = fd.nid');
    $query->join('node__field_round', 'fr', 'fr.entity_id = fp.entity_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = fd.uid');
    $query->condition('fd.status', 1)
      ->condition('ufd.status', 1)
      ->condition('fr.field_round_target_id', $this->round->id());
    $results = $query->distinct()->execute();
    return $results;
  }

  /**
   * @param $type
   * @param $account
   * @param null $flagging
   */
  protected function sendScheduledEmail($type, $account, $flagging = NULL) {

    $site_config = \Drupal::config('system.site');

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();
    $muser_data['round'] = muser_project_get_current_round(TRUE);
    $muser_data['flagging'] = $flagging;
    $params['from'] = muser_system_format_email($site_config->get('name'), $site_config->get('mail'));
    $params['subject'] = $token_service->replace($this->config->get($type . '_email_subject'), [
      'user' => $account,
      'node' => $this->round,
      'muser' => $muser_data,
    ]);
    $params['message'] = $token_service->replace($this->config->get($type . '_email_body'), [
      'user' => $account,
      'node' => $this->round,
      'muser' => $muser_data,
    ]);
    $to = muser_system_format_email($account->getDisplayName(), $account->getEmail());

    /** @var \Drupal\Core\Mail\MailManager $mailer */
    $mailer = \Drupal::service('plugin.manager.mail');
    $module = 'muser_system';
    $key = $type;
    $send = TRUE;

    $result = $mailer->mail($module, $key, $to, $language, $params, NULL, $send);
    $values = [
      '@type' => $type,
      '@recipient' => $to,
    ];
    if (empty($result['send'])) {
      // Trying to queue message.
      if ($result['queued'] !== TRUE) {
        \Drupal::logger('muser_system')->error('Error queuing scheduled @type email to @recipient.', $values);
      }
      else {
        \Drupal::logger('muser_system')->info('Scheduled @type email queued for @recipient.', $values);
      }
    }
    else {
      // Sending message.
      if ($result['result'] !== TRUE) {
        \Drupal::logger('muser_system')->error('Error sending scheduled @type email to @recipient.', $values);
      }
      else {
        \Drupal::logger('muser_system')->info('Scheduled @type email sent to @recipient.', $values);
      }
    }

  }

}
