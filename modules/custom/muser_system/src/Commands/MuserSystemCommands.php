<?php

namespace Drupal\muser_system\Commands;

use Drush\Commands\DrushCommands;
use Drupal\muser_system\ScheduledEmails;

/**
 * Class MuserSystemCommands.
 *
 * @package Drupal\muser_system\Commands
 */
class MuserSystemCommands extends DrushCommands {

  /**
   * Sets current round based on date.
   *
   * @command muser_system:set-current-round
   * @aliases muser-set-round
   * @usage muser_system:set-current-round
   *   Sets the current round and displays a message.
   */
  public function setCurrentRound() {
    if ($new_round = muser_system_set_current_round()) {
      $this->output()->writeln('Current round changed to "' . $new_round->label() . '" (nid: ' . $new_round->id() . ').');
    }
    else {
      $this->output()->writeln('Current round not changed.');
    }
  }

  /**
   * Sets current round based on date.
   *
   * @command muser_system:send-scheduled-emails
   * @aliases muser-send-emails
   * @usage muser_system:send-scheduled-emails
   *   Sends scheduled emails.
   */
  public function setSendScheduledEmails() {
    $config = \Drupal::config('muser_system.settings');
    $state = \Drupal::state();
    $date_time = \Drupal::time();
    $mailer = new ScheduledEmails($config, $state, $date_time);
    $mailer->sendEmails();
  }

}
