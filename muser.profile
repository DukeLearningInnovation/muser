<?php

/**
 * @file
 * Enables modules and site configuration for the Muser installation profile.
 */

use Drupal\node\Entity\Node;
use Drupal\menu_link_content\Entity\MenuLinkContent;


/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 */
function muser_form_install_configure_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $form['admin_account']['account']['name']['#default_value'] = 'sysadmin';
  $form['regional_settings']['site_default_country']['#default_value'] = 'US';
  $form['regional_settings']['date_default_timezone']['#default_value'] = 'America/New_York';
}

/**
 * Implements hook_install_tasks().
 */
function muser_install_tasks() {
  $tasks = [];
  $tasks['muser_post_install_config_import'] = [
    'display_name' => t('Muser post-installation config import'),
    'type' => 'normal',
  ];
  $tasks['muser_content_creation'] = [
    'display_name' => t('Muser initial content creation'),
    'type' => 'normal',
  ];
  $tasks['muser_taxonomy_term_import_form'] = [
    'display_name' => t('Muser taxonomy term import'),
    'type' => 'form',
    'function' => 'Drupal\muser\Installer\Form\ProfileTaxonomyTermImportForm',
  ];
  $tasks['muser_sql_view_creation'] = [
    'display_name' => t('Muser SQL view creation'),
    'type' => 'normal',
  ];
  return $tasks;
}

function muser_post_install_config_import() {

  $config_path = drupal_get_path('profile', 'muser') . '/config/post-install';
  $source = new \Drupal\Core\Config\FileStorage($config_path);
  $config_storage = \Drupal::service('config.storage');

  if (!$items = $source->listAll()) {
    \Drupal::messenger()->addMessage(t('No post-installation config settings to be imported.'));
    return;
  }

  foreach ($items as $item) {
    $config_storage->write($item, $source->read($item));
  } // Loop thru items.

  drupal_flush_all_caches();
  \Drupal::messenger()->addMessage(t('Muser post-installation config settings imported.'));

}

function muser_content_creation() {
  // Create required pages.
  $pages = muser_create_pages();
  muser_create_menu_items($pages);
  \Drupal::messenger()->addMessage(t('Muser initial content created.'));
}

/**
 * Create required pages.
 */
function muser_create_pages() {

  $nodes = [];
  $path = drupal_get_path('profile', 'muser') . '/defaults';
  $pages = json_decode(file_get_contents($path . '/pages.json'));

  $config = \Drupal::configFactory()->getEditable('system.site');

  foreach ($pages as $page) {
    // Create placeholders for pages.
    $node = Node::create([
      'type' => 'page',
      'langcode' => 'en',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => 1,
      'title' => $page->title,
      'body' => ['format' => 'basic_html', 'value' => $page->text],
    ]);
    $node->save();
    if (!empty($page->config)) {
      $config->set($page->config, '/node/' . $node->id());
    }
    $nodes[$page->id] = $node->id();
  } // Loop thru pages to create.

  $config->save(TRUE);

  return $nodes;

}

function muser_create_menu_items($pages) {
  $path = drupal_get_path('profile', 'muser') . '/defaults';
  $items = json_decode(file_get_contents($path . '/menu-items.json'));
  foreach($items as $item) {
    $values = [
      'menu_name' => $item->menu,
      'title' => $item->title,
      'description' => $item->title,
      'weight' => $item->weight,
      'expanded' => FALSE,
    ];
    if (!empty($item->id)) {
      if (empty($pages[$item->id])) {
        continue;
      }
      // route_name, route_param_key, route_parameters
      // entity.node.canonical node=123 ['node' => 123]
      $values['link'] = ['uri' => 'internal:/node/' . $pages[$item->id]];
    }
    else {
      $values['link'] = ['uri' => $item->link];
    }
    $menu_link = MenuLinkContent::create($values);
    $menu_link->save();
  }
}

function muser_sql_view_creation() {
  $path = drupal_get_path('profile', 'muser') . '/defaults/create-views.sql';
  if (!file_exists($path) || !is_readable($path)) {
    return FALSE;
  }
  $full_sql = preg_split('/;\n+/', file_get_contents($path));
  foreach ($full_sql as $sql) {
    if (!$sql = trim($sql)) {
      continue;
    }
    // @todo - view names should (maybe?) be surrounded by { }.
    \Drupal::database()->query($sql);
  } // Loop thru SQL statements.
  \Drupal::messenger()->addMessage(t('Muser SQL views created.'));
}
