<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function muser_system_token_info() {
  $info = [
    'types' => [
      'muser' => [
        'name' => t('Muser'),
        'description' => t('Tokens related to Muser information'),
        'needs-data' => 'muser',
      ],
    ],
    'tokens' => [],
  ];
  $info['tokens']['muser']['school-name'] = [
    'name' => t('School name'),
    'description' => t('Name of the college or university.'),
  ];
  $info['tokens']['muser']['add-project-link'] = [
    'name' => t('"Add project" link'),
    'description' => t('Conditional link to add a new project (will respect Round dates). Can specify text.'),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['project-contacts'] = [
    'name' => t('Project contacts'),
    'description' => t('Names of the mentor and the Principal investigator (if different).'),
  ];
  $info['tokens']['muser']['applicant-name'] = [
    'name' => t('Applicant name'),
    'description' => t('Name of the applicant.'),
  ];
  $info['tokens']['muser']['application-project-name'] = [
    'name' => t('Application project name'),
    'description' => t('Name of the project being applied for.'),
  ];
  $info['tokens']['muser']['application-project-mentor-name'] = [
    'name' => t('Application project Mentor name'),
    'description' => t('Name of the Mentor of the project being applied for.'),
  ];
  $info['tokens']['muser']['application-project-mentor-email'] = [
    'name' => t('Application project Mentor email'),
    'description' => t('Email of the Mentor of the project being applied for.'),
  ];
  $info['tokens']['muser']['round-title'] = [
    'name' => t('Round title'),
    'description' => t('The title of the current Round.'),
  ];
  $info['tokens']['muser']['current-period'] = [
    'name' => t('Current period text'),
    'description' => t('Text describing the current period in the current Round. Can specify an optional Role (student or mentor).'),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['post-projects-start'] = [
    'name' => t('Project posting start'),
    'description' => t('Starting date/time of project posting period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['post-projects-end'] = [
    'name' => t('Project posting end'),
    'description' => t('Ending date/time of project posting period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['application-start'] = [
    'name' => t('Application period start'),
    'description' => t('Starting date/time of Student application period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['application-end'] = [
    'name' => t('Application period end'),
    'description' => t('Ending date/time of Student application period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['review-applications-start'] = [
    'name' => t('Applications review start'),
    'description' => t('Starting date/time of application review period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['review-applications-end'] = [
    'name' => t('Applications review end'),
    'description' => t('Ending date/time of application review period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['contract-period-start'] = [
    'name' => t('Contract period start'),
    'description' => t('Starting date/time of contract period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['contract-period-end'] = [
    'name' => t('Contract period end'),
    'description' => t('Ending date/time of contract period. Can specify an optional date format. See the <a href="@url" target="_blank">PHP manual</a> for available options.', ['@url' => 'http://php.net/manual/function.date.php']),
    'dynamic' => TRUE,
  ];
  $info['tokens']['muser']['review-applications-url'] = [
    'name' => t('Applications review URL'),
    'description' => t('URL for Mentor to review applications for their projects.'),
  ];
  $info['tokens']['muser']['application-contract-accept-url'] = [
    'name' => t('Contract accept URL'),
    'description' => t('URL for Mentor/Student to indicate that they accepted contract.'),
  ];
  // Return them.
  return $info;
}

/**
 * Implements hook_tokens().
 */
function muser_system_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type == 'muser') {

    $muser_data = $data['muser'] ?? NULL;

    $date_token_names = [
      'post-projects-start',
      'post-projects-end',
      'application-start',
      'application-end',
      'contract-period-start',
      'contract-period-end',
      'review-applications-start',
      'review-applications-end',
    ];
    foreach ($date_token_names as $date_token_name) {
      if ($date_tokens = \Drupal::token()->findWithPrefix($tokens, $date_token_name)) {
        foreach ($date_tokens as $format => $original) {
          $replacements[$original] = _muser_system_get_date_text($muser_data, $date_token_name, $format);
          $key = $date_token_name . ':' . $format;
          if (!empty($key)) {
            // Remove this token from our list so we don't try to process it
            // again below where we're using strpos() to get tokens.
            unset($tokens[$date_token_name . ':' . $format]);
          }
        }
      } // Got any date tokens with format specified?
    } // Loop thru different date token names.

    if ($link_tokens = \Drupal::token()->findWithPrefix($tokens, 'add-project-link')) {
      foreach ($link_tokens as $link_text => $original) {
        $text = '';
        $round_nid = (!empty($muser_data['round'])) ? $muser_data['round']->id() : muser_project_get_current_round();
        if ($round_nid && muser_project_round_in_period($round_nid, 'posting')) {
          if (!$link_text) {
            $link_text = t('Create a new project');
          }
          $text = Link::createFromRoute($link_text, 'node.add', ['node_type' => 'project'])->toString();
        }
        $replacements[$original] = $text;
      }
    } // Got any 'Add project' tokens with text specified?

    if ($period_text_tokens = \Drupal::token()->findWithPrefix($tokens, 'current-period')) {
      foreach ($period_text_tokens as $role => $original) {
        $replacements[$original] = _muser_system_get_current_period_text($muser_data, strtolower($role));
      }
    } // Got any period text tokens with role specified?

    foreach ($tokens as $name => $original) {

      // Find the desired token by name.
      switch ($name) {

        case 'school-name':
          $muser_config = \Drupal::config('muser_system.settings');
          $replacements[$original] = $muser_config->get('school_name');
          break;

        case 'add-project-link':
          $text = '';
          $round_nid = (!empty($muser_data['round'])) ? $muser_data['round']->id() : muser_project_get_current_round();
          if ($round_nid && muser_project_round_in_period($round_nid, 'posting')) {
            $text = Link::createFromRoute(t('Create a new project'), 'node.add', ['node_type' => 'project'])->toString();
          }
          $replacements[$original] = $text;
          break;

        case 'project-contacts':
          $contacts = [];
          if (!empty($muser_data['project'])) {
            $contacts[$muser_data['project']->getOwner()->getEmail()] = $muser_data['project']->getOwner()->getDisplayName();
            if ($muser_data['project']->field_pi_email->value) {
              $contacts[$muser_data['project']->field_pi_email->value] = $muser_data['project']->field_pi_name->value;
            }
          }
          $replacements[$original] = implode(' ' . t('and') . ' ', $contacts);;
          break;

        case 'applicant-name':
          $text = '';
          if (!empty($muser_data['flagging'])) {
            $text = $muser_data['flagging']->getOwner()->getDisplayName();
          }
          $replacements[$original] = $text;
          break;

        case 'application-project-name':
          $text = '';
          if (!empty($muser_data['flagging']) && $project = muser_project_get_project_for_flagging($muser_data['flagging'])) {
            $text = $project->label();
          }
          $replacements[$original] = $text;
          break;

        case 'application-project-mentor-name':
          $text = '';
          if (!empty($muser_data['flagging']) && $project = muser_project_get_project_for_flagging($muser_data['flagging'])) {
            $text = $project->getOwner()->getDisplayName();
          }
          $replacements[$original] = $text;
          break;

        case 'application-project-mentor-email':
          $text = '';
          if (!empty($muser_data['flagging']) && $project = muser_project_get_project_for_flagging($muser_data['flagging'])) {
            $text = $project->getOwner()->getEmail();
          }
          $replacements[$original] = $text;
          break;

        case 'review-applications-url':
          $text = '';
          if (!empty($data['user']) && $data['user']->hasRole('mentor')) {
            $text = Url::fromRoute('view.applications.page_new', ['user' => $data['user']->id()], ['absolute' => TRUE])->toString();
          }
          $replacements[$original] = $text;
          break;

        case 'application-contract-accept-url':
          $text = '';
          if (!empty($muser_data['flagging'])) {
            $options = [
              'absolute' => TRUE,
            ];
            $text = Url::fromRoute('muser_project.accept_contract', ['flagging' => $muser_data['flagging']->id()], $options)->toString();
          } // Got a flagging?
          $replacements[$original] = $text;
          break;

        case 'round-title':
          $text = '';
          $round = (!empty($muser_data['round'])) ? $muser_data['round'] : muser_project_get_current_round(TRUE);
          if ($round) {
            $text = $round->label();
          }
          $replacements[$original] = $text;
          break;

        case 'current-period':
          $replacements[$original] = _muser_system_get_current_period_text($muser_data);
          break;

        case (strpos($name, 'post-projects-') === 0):
        case (strpos($name, 'application-') === 0):
        case (strpos($name, 'contract-period-') === 0):
        case (strpos($name, 'review-applications-') === 0):
          $replacements[$original] = _muser_system_get_date_text($muser_data, $name);
          break;

      }
    } // Loop thru all tokens.

  } // Node tokens?

  // Return the replacements.
  return $replacements;

}

/**
 * Format a Round date for display as a token.
 *
 * @param $muser_data
 * @param $name
 *
 * @return string
 */
function _muser_system_get_date_text($muser_data, $name, $format = MUSER_DEFAULT_DATE_TOKEN_FORMAT) {
  $fields = [
    'post-projects' => 'field_post_projects',
    'application' => 'field_apply',
    'contract-period' => 'field_sign_contracts',
    'review-applications' => 'field_accept_applications',
  ];
  $date_type = preg_replace('/-(start|end)$/', '', $name);
  $key = (strpos($name, 'start') !== FALSE) ? 'value' : 'end_value';
  if (empty($fields[$date_type]) || empty($muser_data['round'])) {
    return '';
  }
  $field_data = $muser_data['round']->get($fields[$date_type])->get(0)->getValue();
  $datetime = new DrupalDateTime($field_data[$key], DateTimeItemInterface::STORAGE_TIMEZONE);
  $config_tz = \Drupal::config('system.date')->get('timezone.default');
  date_default_timezone_set($config_tz);
  $date = \Drupal::service('date.formatter')->format($datetime->getTimestamp(), 'custom', $format);
  return $date;
}

function _muser_system_get_current_period_text($muser_data, $role = NULL) {
  $text = [];
  $round_nid = (!empty($muser_data['round'])) ? $muser_data['round']->id() : muser_project_get_current_round(FALSE);
  if ($round_nid) {
    $period = muser_project_get_current_period($round_nid);
    switch ($period) {
      case 'before':
        $values = [
          '@date' => _muser_system_get_date_text($muser_data, 'post-projects-start'),
          '@date2' => _muser_system_get_date_text($muser_data, 'application-start'),
        ];
        if (!$role || $role == 'mentor') {
          $text[] = t('Project posting begins on @date.', $values)->__toString();
        }
        if (!$role || $role == 'student') {
          $text[] = t('Applications will be accepted starting on @date2.', $values)->__toString();
        }
        break;

      case 'posting':
        $values = [
          '@date' => _muser_system_get_date_text($muser_data, 'post-projects-end'),
          '@date2' => _muser_system_get_date_text($muser_data, 'application-start'),
        ];
        if (!$role || $role == 'mentor') {
          $text[] = t('Project posting period continues until @date.', $values)->__toString();
        }
        if (!$role || $role == 'student') {
          $text[] = t('Applications will be accepted starting on @date2.', $values)->__toString();
        }
        break;

      case 'application':
        $values = [
          '@date' => _muser_system_get_date_text($muser_data, 'application-end'),
          '@date2' => _muser_system_get_date_text($muser_data, 'review-applications-start'),
        ];
        if (!$role || $role == 'mentor') {
          $text[] = t('Application review starts on @date2.', $values)->__toString();
        }
        if (!$role || $role == 'student') {
          $text[] = t('Applications will be accepted until @date.', $values)->__toString();
        }
        break;

      case 'acceptance':
        $values = [
          '@date' => _muser_system_get_date_text($muser_data, 'review-applications-end'),
        ];
        $text[] = t('Application review continues until @date.', $values)->__toString();
        break;

      case 'contract':
        $values = [
          '@date' => _muser_system_get_date_text($muser_data, 'contract-period-end'),
        ];
        $text[] = t('Contract period continues until @date.', $values)->__toString();
        break;

      case 'after':
        $text[] = t('The current Round is complete.')->__toString();
    }
  }
  return implode(' ', $text);
}
