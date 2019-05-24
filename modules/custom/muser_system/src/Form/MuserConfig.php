<?php

namespace Drupal\muser_system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class MuserConfig.
 */
class MuserConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'muser_config';
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

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General information'),
      '#open' => TRUE,
    ];
    $form['general']['school_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School name'),
      '#description' => $this->t('The name of the college or university. May be used as a token in emails, etc.'),
      '#required' => TRUE,
      '#default_value' => $config->get('school_name'),
    ];

    $form['general']['blog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blog title'),
      '#description' => $this->t('The title to use for the site Blog. Note that any menu items will need to be changed separately.'),
      '#required' => TRUE,
      '#default_value' => $config->get('blog_title') ?: $this->t('Blog'),
    ];

    $form['general']['copyright_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright message text'),
      '#description' => $this->t('If set, this text will be displayed in the footer after the year.'),
      '#default_value' => $config->get('copyright_message'),
    ];

    $options = ['drupal' => $this->t('Standard Drupal user management')];
    $module_handler = \Drupal::moduleHandler();
    // @todo - add other Shib modules?
    if (1 || $module_handler->moduleExists('basicshib')) {
      $options['basicshib'] = $this->t('BasicShib auth');
    }
    if ($module_handler->moduleExists('shib_auth')) {
      $options['shib_auth'] = $this->t('Shibboleth authentication');
    }

    if (count($options) > 1) {
      $form['user_management'] = [
        '#type' => 'details',
        '#title' => $this->t('User management'),
        '#open' => TRUE,
      ];
      $form['user_management']['user_login_method'] = [
        '#type' => 'radios',
        '#title' => $this->t('User login method'),
        '#description' => $this->t('How the site will manage use logins.'),
        '#options' => $options,
        '#default_value' => $config->get('user_login_method') ?? 'drupal',
      ];
      $form['user_management']['user_management_restricted'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Restrict user management?'),
        '#description' => $this->t('If checked, users will not be allowed to change their email address, password, etc. via their User profile page.'),
        '#default_value' => $config->get('user_management_restricted') ?? FALSE,
      ];
    }
    else {
      $form['user_management']['user_management_method'] = [
        '#type' => 'hidden',
        '#value' => 'drupal',
      ];
    }

    $form['rounds'] = [
      '#type' => 'details',
      '#title' => $this->t('Rounds'),
      '#open' => TRUE,
    ];
    $form['rounds']['default_num_applications'] = [
      '#type' => 'number',
      '#title' => $this->t('Default number of applications per Student per Round'),
      '#description' => $this->t('The default number of applications per student that will be allowed for a new Round. Can be changed per-Round.<br/>Setting this to 0 for a Round means that there is no limit.'),
      '#size' => 5,
      '#default_value' => $config->get('default_num_applications') ?? MUSER_NUM_APPLICATIONS,
      '#min' => 0,
      '#attributes' => [
        'step' => 1,
      ],
    ];

    $form['hero_block'] = [
      '#type' => 'details',
      '#title' => $this->t('Hero block messages'),
      '#open' => FALSE,
    ];
    $form['hero_block']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('These are the messages that will be shown to users with each role in the "hero" block (shown on the home page by default). May include tokens.'),
    ];
    $value = $config->get('hero_block_anonymous');
    $form['hero_block']['hero_block_anonymous'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Anonymous'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];
    $value = $config->get('hero_block_student');
    $form['hero_block']['hero_block_student'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Student'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];
    $value = $config->get('hero_block_mentor');
    $form['hero_block']['hero_block_mentor'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Mentor'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];
    $form['hero_block']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'muser'],
      '#show_restricted' => TRUE,
    ];

    $form['roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Role descriptions'),
      '#open' => FALSE,
    ];
    $form['roles']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('These are the descriptions of the <em>Student</em> and <em>Mentor</em> roles that users will see when completing their profile.'),
    ];
    $value = $config->get('role_description_student');
    $form['roles']['role_description_student'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Student'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];
    $value = $config->get('role_description_mentor');
    $form['roles']['role_description_mentor'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Mentor'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];

    $form['application_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Application help'),
      '#open' => FALSE,
    ];
    $form['application_help']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('These guidelines with be shown on the "My favorites" page to help Students with their essay. This might mention a recommended length, remind them to not include their name, etc.'),
    ];
    $value = $config->get('application_essay_guidelines');
    $form['application_help']['application_essay_guidelines'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Guidelines'),
      '#format' => $value['format'] ?? 'basic_html',
      '#default_value' => $value['value'] ?? NULL,
    ];

    $definition = FieldConfig::loadByName('flagging', 'favorites', 'field_status');
    $settings = $definition->getSettings();
    $form['#application_statuses'] = $settings['allowed_values'];
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Application status values'),
      '#open' => FALSE,
    ];
    $form['status']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('These are the descriptions of the status values for <em>Applications</em>.'),
    ];
    foreach ($form['#application_statuses'] as $key => $text) {
      $name = 'application_status_' . $key;
      $value = $config->get($name);
      $form['status'][$name] = [
        '#type' => 'text_format',
        '#title' => $text,
        '#format' => $value['format'] ?? 'basic_html',
        '#default_value' => $value['value'] ?? NULL,
      ];
    } // Loop thru status values.

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('muser_system.settings');

    $config->set('school_name', $values['school_name']);
    $config->set('blog_title', $values['blog_title']);
    $config->set('copyright_message', $values['copyright_message']);
    $config->set('default_num_applications', $values['default_num_applications']);

    if (empty($values['user_login_method'])) {
      $values['user_login_method'] = 'drupal';
    }
    if ($values['user_login_method'] == 'drupal') {
      $values['user_management_restricted'] = FALSE;
    }
    $config->set('user_login_method', $values['user_login_method']);
    $config->set('user_management_restricted', $values['user_management_restricted']);

    $config->set('hero_block_anonymous', $values['hero_block_anonymous']);
    $config->set('hero_block_student', $values['hero_block_student']);
    $config->set('hero_block_mentor', $values['hero_block_mentor']);

    $config->set('role_description_student', $values['role_description_student']);
    $config->set('role_description_mentor', $values['role_description_mentor']);

    $config->set('application_essay_guidelines', $values['application_essay_guidelines']);

    foreach ($form['#application_statuses'] as $key => $text) {
      $name = 'application_status_' . $key;
      $config->set($name, $values[$name]);
    } // Loop thru status values.

    $config->save();
    parent::submitForm($form, $form_state);

  }

}
