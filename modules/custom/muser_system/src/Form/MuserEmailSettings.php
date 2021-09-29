<?php

namespace Drupal\muser_system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MuserEmails.
 */
class MuserEmailSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'muser_email_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'muser_system.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('muser_system.settings');

    $form['#attached']['library'][] = 'muser_system/token-preview';
    $form['#attributes']['class'][] = 'token-preview-enabled';

    // Mentor role-request emails.
    $form['mentor'] = [
      '#type' => 'details',
      '#title' => $this->t('Mentor role requests'),
      '#open' => TRUE,
    ];

    $form['mentor']['mentor_requests'] = [
      '#type' => 'details',
      '#title' => $this->t('Request notification'),
      '#open' => FALSE,
    ];
    $form['mentor']['mentor_requests']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent when a user requests the Mentor role.')
      . '</div>',
    ];
    $recipients = $config->get('mentor_request_recipients') ?? [];
    $form['mentor']['mentor_requests']['mentor_request_recipients'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#description' => $this->t('Enter the email addresses (one per line) that should receive requests for the Mentor role.'),
      '#rows' => 5,
      '#default_value' => implode("\n", $recipients),
    ];
    $form['mentor']['mentor_requests']['mentor_request_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('mentor_request_email_subject'),
    ];
    $form['mentor']['mentor_requests']['mentor_request_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t("Should include the link to edit the requestor's profile. May include tokens."),
      '#default_value' => $config->get('mentor_request_email_body'),
      '#rows' => 10,
    ];
    $form['mentor']['mentor_requests']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'mentor_request_email_body',
      ],
    ];
    $form['mentor']['mentor_requests']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['mentor']['grant_notification'] = [
      '#type' => 'details',
      '#title' => $this->t('Grant notification'),
      '#open' => FALSE,
    ];
    $form['mentor']['grant_notification']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to a Mentor when they are granted the Mentor role.')
      . '</div>',
    ];
    $form['mentor']['grant_notification']['mentor_grant_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('mentor_grant_email_subject'),
    ];
    $form['mentor']['grant_notification']['mentor_grant_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('mentor_grant_email_body'),
      '#rows' => 10,
    ];
    $form['mentor']['grant_notification']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'mentor_grant_email_body',
      ],
    ];
    $form['mentor']['grant_notification']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'muser'],
      '#show_restricted' => TRUE,
    ];

    // New-project emails.
    $form['new_project'] = [
      '#type' => 'details',
      '#title' => $this->t('New project notification'),
      '#open' => FALSE,
    ];
    $form['new_project']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to the Mentor and the Principal investigator when a new Project is created.')
      . '</div>',
    ];
    $form['new_project']['new_project_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('new_project_email_subject'),
    ];
    $form['new_project']['new_project_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t("Should include the link to the new project. May include tokens."),
      '#default_value' => $config->get('new_project_email_body'),
      '#rows' => 10,
    ];
    $form['new_project']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'new_project_email_body',
      ],
    ];
    $form['new_project']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    // Mentor-reminder emails.
    $form['mentor_reminder'] = [
      '#type' => 'details',
      '#title' => $this->t('Mentor reminder emails'),
      '#open' => TRUE,
    ];

    $form['mentor_reminder']['post_projects_start'] = [
      '#type' => 'details',
      '#title' => $this->t('Before "First day to post projects"'),
      '#open' => FALSE,
    ];
    $form['mentor_reminder']['post_projects_start']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to mentors before the first day to post Projects.')
      . '</div>',
    ];
    $form['mentor_reminder']['post_projects_start']['post_projects_start_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the first day to post Projects to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the start of the period.'),
      '#default_value' => $config->get('post_projects_start_email_offset'),
    ];
    $form['mentor_reminder']['post_projects_start']['post_projects_start_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('post_projects_start_email_subject'),
    ];
    $form['mentor_reminder']['post_projects_start']['post_projects_start_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('post_projects_start_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_reminder']['post_projects_start']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'post_projects_start_email_body',
      ],
    ];
    $form['mentor_reminder']['post_projects_start']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['mentor_reminder']['post_projects_end'] = [
      '#type' => 'details',
      '#title' => $this->t('Before "Last day to post projects"'),
      '#open' => FALSE,
    ];
    $form['mentor_reminder']['post_projects_end']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to mentors before the last day to post Projects.')
      . '</div>',
    ];
    $form['mentor_reminder']['post_projects_end']['post_projects_end_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the last day to post Projects to send this email (e.g. "now", "-1 day", "-2 days 12 hours"). Leaving this empty means at right the end of the period.'),
      '#default_value' => $config->get('post_projects_end_email_offset'),
    ];
    $form['mentor_reminder']['post_projects_end']['post_projects_end_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('post_projects_end_email_subject'),
    ];
    $form['mentor_reminder']['post_projects_end']['post_projects_end_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('post_projects_end_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_reminder']['post_projects_end']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'post_projects_end_email_body',
      ],
    ];
    $form['mentor_reminder']['post_projects_end']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];
/*
c. Admin sets 4 additional emails:

To mentor that needs to sign contract with list of projects/applicants(at start of period)
To student that needs to sign contract with list of projects (at start of period)
Reminder to mentor that still needs to sign contract(s), if they haven't already (before end of period)
Reminder to student that still needs to sign contract(s), if they haven't already (before end of period)
Each of these emails will contain a link back to a form on the Muser site for them to submit.

d. For mentors, the form will have these choices:
[  ] I have not sent this student a contract.
[  ] I have sent this student a contract.
[Submit]

e. For students, the form will have these choices:
[  ]  I have not received a contract from my mentor.
[  ]  I have received a contract and have not signed it.
[  ]  I have received a contract and have signed it.
[Submit]
 */
    $form['contract'] = [
      '#type' => 'details',
      '#title' => $this->t('Contract emails'),
      '#open' => TRUE,
    ];
    $form['contract']['contract_reminder_mentor_start'] = [
      '#type' => 'details',
      '#title' => $this->t('To Mentors - Start of contract period'),
      '#open' => FALSE,
    ];
    $form['contract']['contract_reminder_mentor_start']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
        . $this->t('This email will be sent to mentors at the start of the contract period.')
        . '</div>',
    ];
    $form['contract']['contract_reminder_mentor_start']['contract_reminder_mentor_start_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the first day to sign contracts to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the start of the period.'),
      '#default_value' => $config->get('contract_reminder_mentor_start_email_offset'),
    ];
    $form['contract']['contract_reminder_mentor_start']['contract_reminder_mentor_start_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('contract_reminder_mentor_start_email_subject'),
    ];
    $form['contract']['contract_reminder_mentor_start']['contract_reminder_mentor_start_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('contract_reminder_mentor_start_email_body'),
      '#rows' => 10,
    ];
    $form['contract']['contract_reminder_mentor_start']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'contract_reminder_mentor_start_email_body',
      ],
    ];
    $form['contract']['contract_reminder_mentor_start']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];
    $form['contract']['contract_reminder_mentor_end'] = [
      '#type' => 'details',
      '#title' => $this->t('To Mentors - End of contract period'),
      '#open' => FALSE,
    ];
    $form['contract']['contract_reminder_mentor_end']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
        . $this->t('This email will be sent to mentors at the end of the contract period.')
        . '</div>',
    ];
    $form['contract']['contract_reminder_mentor_end']['contract_reminder_mentor_end_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the last day to sign contracts to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the end of the period.'),
      '#default_value' => $config->get('contract_reminder_mentor_end_email_offset'),
    ];
    $form['contract']['contract_reminder_mentor_end']['contract_reminder_mentor_end_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('contract_reminder_mentor_end_email_subject'),
    ];
    $form['contract']['contract_reminder_mentor_end']['contract_reminder_mentor_end_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('contract_reminder_mentor_end_email_body'),
      '#rows' => 10,
    ];
    $form['contract']['contract_reminder_mentor_end']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'contract_reminder_mentor_end_email_body',
      ],
    ];
    $form['contract']['contract_reminder_mentor_end']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];
    $form['contract']['contract_reminder_student_start'] = [
      '#type' => 'details',
      '#title' => $this->t('To Students - Start of contract period'),
      '#open' => FALSE,
    ];
    $form['contract']['contract_reminder_student_start']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
        . $this->t('This email will be sent to students at the start of the contract period.')
        . '</div>',
    ];
    $form['contract']['contract_reminder_student_start']['contract_reminder_student_start_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the first day to sign contracts to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the start of the period.'),
      '#default_value' => $config->get('contract_reminder_student_start_email_offset'),
    ];
    $form['contract']['contract_reminder_student_start']['contract_reminder_student_start_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('contract_reminder_student_start_email_subject'),
    ];
    $form['contract']['contract_reminder_student_start']['contract_reminder_student_start_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('contract_reminder_student_start_email_body'),
      '#rows' => 10,
    ];
    $form['contract']['contract_reminder_student_start']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'contract_reminder_student_start_email_body',
      ],
    ];
    $form['contract']['contract_reminder_student_start']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];
    $form['contract']['contract_reminder_student_end'] = [
      '#type' => 'details',
      '#title' => $this->t('To Students - End of contract period'),
      '#open' => FALSE,
    ];
    $form['contract']['contract_reminder_student_end']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
        . $this->t('This email will be sent to students at the end of the contract period.')
        . '</div>',
    ];
    $form['contract']['contract_reminder_student_end']['contract_reminder_student_end_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the last day to sign contracts to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the end of the period.'),
      '#default_value' => $config->get('contract_reminder_student_end_email_offset'),
    ];
    $form['contract']['contract_reminder_student_end']['contract_reminder_student_end_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('contract_reminder_student_end_email_subject'),
    ];
    $form['contract']['contract_reminder_student_end']['contract_reminder_student_end_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('contract_reminder_student_end_email_body'),
      '#rows' => 10,
    ];
    $form['contract']['contract_reminder_student_end']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'contract_reminder_student_end_email_body',
      ],
    ];
    $form['contract']['contract_reminder_student_end']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['mentor_reminder']['review_applications_start'] = [
      '#type' => 'details',
      '#title' => $this->t('Before the "First day to accept applications"'),
      '#open' => FALSE,
    ];
    $form['mentor_reminder']['review_applications_start']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to mentors before the first day to accept applications.')
      . '</div>',
    ];
    $form['mentor_reminder']['review_applications_start']['review_applications_start_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the first day to accept applications to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the start of the period.'),
      '#default_value' => $config->get('review_applications_start_email_offset'),
    ];
    $form['mentor_reminder']['review_applications_start']['review_applications_start_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('review_applications_start_email_subject'),
    ];
    $form['mentor_reminder']['review_applications_start']['review_applications_start_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('review_applications_start_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_reminder']['review_applications_start']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'review_applications_start_email_body',
      ],
    ];
    $form['mentor_reminder']['review_applications_start']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['mentor_reminder']['review_applications_end'] = [
      '#type' => 'details',
      '#title' => $this->t('Before the "Last day to accept applications"'),
      '#open' => FALSE,
    ];
    $form['mentor_reminder']['review_applications_end']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to mentors before the last day to accept applications.')
      . '</div>',
    ];
    $form['mentor_reminder']['review_applications_end']['review_applications_end_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the last day to accept applications to send this email (e.g. "now", "-1 day", "-2 days 12 hours"). Leaving this empty means at right the end of the period.'),
      '#default_value' => $config->get('review_applications_end_email_offset'),
    ];
    $form['mentor_reminder']['review_applications_end']['review_applications_end_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('review_applications_end_email_subject'),
    ];
    $form['mentor_reminder']['review_applications_end']['review_applications_end_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('review_applications_end_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_reminder']['review_applications_end']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'review_applications_end_email_body',
      ],
    ];
    $form['mentor_reminder']['review_applications_end']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    // After-round email.
    $form['after_round'] = [
      '#type' => 'details',
      '#title' => $this->t('After-Round email'),
      '#open' => FALSE,
    ];
    $form['after_round']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to students at the end of a Round.')
      . '</div>',
    ];
    $form['after_round']['after_round_email_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sending relative date/time'),
      '#description' => $this->t('How far from the end of a Round to send this email (e.g. "now", "+1 day", "+2 days 12 hours"). Leaving this empty means at right the end of the period.'),
      '#default_value' => $config->get('after_round_email_offset'),
    ];
    $form['after_round']['after_round_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('after_round_email_subject'),
    ];
    $form['after_round']['after_round_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('after_round_email_body'),
      '#rows' => 10,
    ];
    $form['after_round']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'after_round_email_body',
      ],
    ];
    $form['after_round']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    // Mentor-student emails.
    $form['mentor_student'] = [
      '#type' => 'details',
      '#title' => $this->t('Mentor-to-student emails'),
      '#open' => TRUE,
    ];

    $form['mentor_student']['accepted'] = [
      '#type' => 'details',
      '#title' => $this->t('Accepted email'),
      '#open' => FALSE,
    ];
    $form['mentor_student']['accepted']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to students when they are accepted for a Project.')
      . '</div>',
    ];
    $form['mentor_student']['accepted']['student_accepted_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('student_accepted_email_subject'),
    ];
    $form['mentor_student']['accepted']['student_accepted_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('student_accepted_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_student']['accepted']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'student_accepted_email_body',
      ],
    ];
    $form['mentor_student']['accepted']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['mentor_student']['rejected'] = [
      '#type' => 'details',
      '#title' => $this->t('Rejected email'),
      '#open' => FALSE,
    ];
    $form['mentor_student']['rejected']['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>'
      . $this->t('This email will be sent to students when they are rejected for a Project.')
      . '</div>',
    ];
    $form['mentor_student']['rejected']['student_rejected_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('The subject of the email. May include tokens.'),
      '#default_value' => $config->get('student_rejected_email_subject'),
    ];
    $form['mentor_student']['rejected']['student_rejected_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#description' => $this->t('May include tokens.'),
      '#default_value' => $config->get('student_rejected_email_body'),
      '#rows' => 10,
    ];
    $form['mentor_student']['rejected']['token_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => ['token-preview-button'],
        'data-field-name' => 'student_rejected_email_body',
      ],
    ];
    $form['mentor_student']['rejected']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'muser'],
      '#show_restricted' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    $form['#mentor_request_recipients'] = [];

    if (!empty($values['mentor_request_recipients'])) {
      $emails = explode("\n", $values['mentor_request_recipients']);
      foreach ($emails as $email) {
        $email = trim($email);
        if (!$email) {
          continue;
        }
        if (!\Drupal::service('email.validator')->isValid($email)) {
          $form_state->setErrorByName('mentor_request_recipients', $this->t('Invalid Mentor request recipient email address.'));
        }
        else {
          $form['#mentor_request_recipients'][] = $email;
        }
      } // Loop thru lines.
    } // Got recipients set?

    $offset_fields = [
      'post_projects_start_email_offset' => t('Before "First day to post projects" Sending relative date/time'),
      'post_projects_end_email_offset' => t('Before "Last day to post projects" Sending relative date/time'),
      'review_applications_start_email_offset' => t('Before "First day to accept applications" Sending relative date/time'),
      'review_applications_end_email_offset' => t('Before "Last day to accept applications" Sending relative date/time'),
      'contract_reminder_mentor_start_email_offset' => t('To Mentors - Start of contract period Sending relative date/time'),
      'contract_reminder_mentor_end_email_offset' => t('To Mentors - End of contract period Sending relative date/time'),
      'contract_reminder_student_start_email_offset' => t('To Students - Start of contract period Sending relative date/time'),
      'contract_reminder_student_end_email_offset' => t('To Students - End of contract period Sending relative date/time'),
      'after_round_email_offset' => t('After-round email Sending relative date/time'),
    ];
    foreach ($offset_fields as $field => $label) {
      if (empty($values[$field])) {
        continue;
      }
      if (strtotime(muser_system_reverse_time_offset($values[$field])) === FALSE) {
        $form_state->setErrorByName($field, $this->t('@label is not valid.', ['@label' => $label]));
      }
    } // Loop thru offset fields.

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('muser_system.settings');

    $config->set('mentor_request_recipients', array_unique($form['#mentor_request_recipients']));

    $fields = [
      'mentor_request_email_subject',
      'mentor_request_email_body',
      'mentor_grant_email_subject',
      'mentor_grant_email_body',
      'new_project_email_subject',
      'new_project_email_body',
      'post_projects_start_email_offset',
      'post_projects_start_email_subject',
      'post_projects_start_email_body',
      'post_projects_end_email_offset',
      'post_projects_end_email_subject',
      'post_projects_end_email_body',
      'review_applications_start_email_offset',
      'review_applications_start_email_subject',
      'review_applications_start_email_body',
      'review_applications_end_email_offset',
      'review_applications_end_email_subject',
      'review_applications_end_email_body',
      'contract_reminder_mentor_start_email_offset',
      'contract_reminder_mentor_start_email_subject',
      'contract_reminder_mentor_start_email_body',
      'contract_reminder_mentor_end_email_offset',
      'contract_reminder_mentor_end_email_subject',
      'contract_reminder_mentor_end_email_body',
      'contract_reminder_student_start_email_offset',
      'contract_reminder_student_start_email_subject',
      'contract_reminder_student_start_email_body',
      'contract_reminder_student_end_email_offset',
      'contract_reminder_student_end_email_subject',
      'contract_reminder_student_end_email_body',
      'after_round_email_offset',
      'after_round_email_subject',
      'after_round_email_body',
      'student_accepted_email_subject',
      'student_accepted_email_body',
      'student_rejected_email_subject',
      'student_rejected_email_body',
    ];

    foreach ($fields as $field) {
      $config->set($field, $values[$field]);
    }

    $config->save();
    parent::submitForm($form, $form_state);

  }

}
