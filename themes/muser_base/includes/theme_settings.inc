<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;

function _muser_base_form_system_theme_settings_alter(&$form, &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }
  $theme_folder = drupal_get_path('theme', 'muser_base');
  $possible_presets = [
    $theme_folder . '/images/logos/header-logo--muser.svg' => 'muser',
    'custom' => 'Custom',
  ];
  $form['logo']['settings']['logo_presets'] = [
    '#type' => 'radios',
    '#weight' => '-50',
    '#options' => $possible_presets,
    '#default_value' => theme_get_setting('logo_presets') ?? 'custom',
    '#states' => ['invisible' => ['input[name="default_logo"]' => ['checked' => TRUE]]],
  ];
  $form['logo']['settings']['logo_path']['#states']['visible'] = ['input[name="logo_presets"]' => ['value' => 'custom']];

  //  $form['copyright_holder'] = [
  //    '#type' => 'textfield',
  //    '#title' => t('Copyright text'),
  //    '#default_value' => theme_get_setting('copyright_holder'),
  //    '#description' => t('This appears after the "© 20XX" in the footer.'),
  //  ];
//  $form['menu_header_text'] = [
//    '#type' => 'textfield',
//    '#title' => t('Menu header text'),
//    '#default_value' => theme_get_setting('menu_header_text'),
//    '#description' => t('This is the text that appears in the header when the main menu of the site is opened.'),
//  ];

  $form['logo_header_style'] = [
    '#type' => 'select',
    '#options' => [
      'normal_header_normal_logo' => 'Normal Header, Normal Logo (max 48x150px)',
      'normal_header_large_logo' => 'Normal Header, Large Logo (max 64x200px)',
      'tall_header_large_logo' => 'Tall Header, Large Logo (max 100x200px)',
    ],
    '#title' => t('Logo/header style'),
    '#default_value' => theme_get_setting('logo_header_style'),
  ];

  $colorset_options = [];
  $colorsets = muser_base_get_colorsets();
  foreach ($colorsets as $key => $colorset) {
    if (!empty($colorset['name'])) {
      $colorset_options[$key] = $colorset['name'];
    }
  }
  $colorset_options['custom'] = t('Custom');
  $form['colorset'] = [
    '#type' => 'select',
    '#attributes' => ['data-colorset-options' => json_encode($colorsets)],
    '#options' => $colorset_options,
    '#title' => t('Site Accent Color Set'),
    '#default_value' => theme_get_setting('colorset'),
    '#description' => t("The color set for the site."),
  ];

  $condition = ['select[name="colorset"]' => ['value' => 'custom']];
  $form['custom_primary_color'] = [
    '#type' => 'textfield',
    '#title' => t('Primary Accent Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'PRIMARY_COLOR'],
    '#default_value' => theme_get_setting('custom_primary_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];
  $form['custom_secondary_color'] = [
    '#type' => 'textfield',
    '#title' => t('Secondary Accent Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'SECONDARY_COLOR'],
    '#default_value' => theme_get_setting('custom_secondary_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_background_color'] = [
    '#type' => 'textfield',
    '#title' => t('Site Background Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'BACKGROUND_COLOR'],
    '#default_value' => theme_get_setting('custom_background_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_title_color'] = [
    '#type' => 'textfield',
    '#title' => t('Title Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TITLE_COLOR'],
    '#default_value' => theme_get_setting('custom_title_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_text_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_text_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_status_message_color'] = [
    '#type' => 'textfield',
    '#title' => t('Status Message Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_status_message_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_error_message_color'] = [
    '#type' => 'textfield',
    '#title' => t('Error Message Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_error_message_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_warning_message_color'] = [
    '#type' => 'textfield',
    '#title' => t('Warning Message Color'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_warning_message_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_message_status_text_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text color for status messages.'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_message_status_text_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_message_warning_text_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text color for warning messages.'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_message_warning_text_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_message_error_text_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text color for error messages.'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_message_error_text_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_text_over_primary_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text Color over Primary Color. Set dark over light colors and light over dark colors.'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_text_over_primary_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  $form['custom_text_over_secondary_color'] = [
    '#type' => 'textfield',
    '#title' => t('Text Color over Secondary Color. Set dark over light colors and light over dark colors.'),
    '#attributes' => ['class' => ['color-field'], 'data-color' => 'TEXT_COLOR'],
    '#default_value' => theme_get_setting('custom_text_over_secondary_color'),
    '#states' => [
      'visible' => $condition,
      'required' => $condition,
    ],
  ];

  //  $form['accent_color_light_change_factor'] = [
  //    '#type' => 'textfield',
  //    '#title' => t('Lightening factor used for creating of the light accent'),
  //    '#attributes' => [
  //      'class' => ['color-field--dependant'],
  //      'data-dependant-field' => 'accent_color_base',
  //      'data-color' => 'light',
  //    ],
  //    '#default_value' => theme_get_setting('accent_color_light_change_factor') ?: '0.2',
  //    '#states' => [
  //      'visible' => [
  //        'select[name="accent_colorset_type"]' => ['value' => 'custom'],
  //        'input[name="accent_color_advanced_custom"]' => ['checked' => FALSE],
  //      ],
  //    ],
  //  ];
  //  $form['accent_color_text_change_factor'] = [
  //    '#type' => 'textfield',
  //    '#title' => t('Darkening factor used for creating of the text accent'),
  //    '#attributes' => [
  //      'class' => ['color-field--dependant'],
  //      'data-dependant-field' => 'accent_color_base',
  //      'data-amount-invert' => '1',
  //      'data-color' => 'text',
  //    ],
  //    '#default_value' => theme_get_setting('accent_color_text_change_factor') ?: '0.2',
  //    '#states' => [
  //      'visible' => [
  //        'select[name="accent_colorset_type"]' => ['value' => 'custom'],
  //        'input[name="accent_color_advanced_custom"]' => ['checked' => FALSE],
  //      ],
  //    ],
  //  ];
  //  $form['accent_color_light'] = [
  //    '#type' => 'textfield',
  //    '#title' => t('Custom Site Accent Color (light)'),
  //    '#attributes' => ['class' => ['color-field'], 'data-color' => 'light'],
  //    '#default_value' => theme_get_setting('accent_color_light'),
  //    '#states' => [
  //      'visible' => [
  //        'select[name="accent_colorset_type"]' => ['value' => 'custom'],
  //        'input[name="accent_color_advanced_custom"]' => ['checked' => TRUE],
  //      ],
  //    ],
  //  ];
  //  $form['accent_color_text'] = [
  //    '#type' => 'textfield',
  //    '#title' => t('Custom Site Accent Color (text)'),
  //    '#attributes' => ['class' => ['color-field'], 'data-color' => 'text'],
  //    '#default_value' => theme_get_setting('accent_color_text'),
  //    '#states' => [
  //      'visible' => [
  //        'select[name="accent_colorset_type"]' => ['value' => 'custom'],
  //        'input[name="accent_color_advanced_custom"]' => ['checked' => TRUE],
  //      ],
  //    ],
  //  ];
  //  $form['accent_colors_demo'] = [
  //    '#type' => 'markup',
  //    '#markup' => '<div class="accent-demo-wrapper"><div class="accent-light"><span class="accent-base">Accent</span><h3>This background is the light accent</h3></div><div class="text-content"><h2 class="accent-text">This header is the Text Accent</h2><p>Nam tristique ipsum ut suscipit varius. Quisque aliquam rutrum mauris, eget tincidunt urna rutrum a. Aliquam a ligula faucibus, sagittis ante a, feugiat turpis. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla volutpat leo mauris, in efficitur mauris tempus id. Aliquam ullamcorper nibh quis suscipit vehicula.</p></div><a href="#" class="refresh-button button button--primary">Refresh Demo</a></div>',
  //    '#title' => t('Colors Demo'),
  //  ];
  $form['#submit'][] = 'muser_base_handle_form_submit';
  $form['#attached']['library'][] = 'muser_base/theme-settings-form';
}

function muser_base_get_colorsets() {
  $colorsets = [
    'colorset_devil' => [
      'name' => t('Devil (Dark blue)'),
      'PRIMARY_COLOR' => '#001A57',
      'SECONDARY_COLOR' => '#339898',
      'BACKGROUND_COLOR' => '#F3F2F1',
      'TITLE_COLOR' => '#001A57',
      'TEXT_COLOR' => '#262626',
      'MESSAGE_ERROR_BG' => '#C84E00',
      'MESSAGE_WARNING_BG' => '#E89923',
      'MESSAGE_STATUS_BG' => '#A1B70D',
      'MESSAGE_ERROR_TEXT' => '#FFFFFF',
      'MESSAGE_WARNING_TEXT' => '#FFFFFF',
      'MESSAGE_STATUS_TEXT' => '#FFFFFF',
      'TEXT_OVER_PRIMARY' => '#FFFFFF',
      'TEXT_OVER_SECONDARY' => '#FFFFFF',
    ],
    'colorset_wolfy' => [
      'name' => t('Wolfy (Red)'),
      'PRIMARY_COLOR' => '#CC0000',
      'SECONDARY_COLOR' => '#427E93',
      'BACKGROUND_COLOR' => '#F3F2F1',
      'TITLE_COLOR' => '#001A57',
      'TEXT_COLOR' => '#262626',
      'MESSAGE_ERROR_BG' => '#D14905',
      'MESSAGE_WARNING_BG' => '#FDD726',
      'MESSAGE_STATUS_BG' => '#6F7D1C',
      'MESSAGE_ERROR_TEXT' => '#FFFFFF',
      'MESSAGE_WARNING_TEXT' => '#FFFFFF',
      'MESSAGE_STATUS_TEXT' => '#FFFFFF',
      'TEXT_OVER_PRIMARY' => '#FFFFFF',
      'TEXT_OVER_SECONDARY' => '#FFFFFF',
    ],
    'colorset_ram' => [
      'name' => t('Ram (Light blue)'),
      'PRIMARY_COLOR' => '#4B9CD3',
      'SECONDARY_COLOR' => '#2A696A',
      'BACKGROUND_COLOR' => '#F3F2F1',
      'TITLE_COLOR' => '#001A57',
      'TEXT_COLOR' => '#262626',
      'MESSAGE_ERROR_BG' => '#DADDC0',
      'MESSAGE_WARNING_BG' => '#E89923',
      'MESSAGE_STATUS_BG' => '#A1B70D',
      'MESSAGE_ERROR_TEXT' => '#FFFFFF',
      'MESSAGE_WARNING_TEXT' => '#FFFFFF',
      'MESSAGE_STATUS_TEXT' => '#FFFFFF',
      'TEXT_OVER_PRIMARY' => '#FFFFFF',
      'TEXT_OVER_SECONDARY' => '#FFFFFF',
    ],
  ];
  // Allow modules to alter/add colorsets.
  \Drupal::moduleHandler()->alter('muser_colorsets', $colorsets);
  return $colorsets;
}

function muser_base_get_accent_colors($colorset = NULL, $form_values = NULL) {
  $colorset = $colorset ?: theme_get_setting('colorset', 'muser_base');

  if (strpos($colorset, 'colorset_') !== FALSE) {
    return muser_base_get_colorsets()[$colorset];
  }
  $colorset = [
    'PRIMARY_COLOR' => $form_values['custom_primary_color']['#value'] ?? theme_get_setting('custom_primary_color', 'muser_base'),
    'SECONDARY_COLOR' => $form_values['custom_secondary_color']['#value'] ?? theme_get_setting('custom_secondary_color', 'muser_base'),
    'BACKGROUND_COLOR' => $form_values['custom_background_color']['#value'] ?? theme_get_setting('custom_background_color', 'muser_base'),
    'TITLE_COLOR' => $form_values['custom_title_color']['#value'] ?? theme_get_setting('custom_title_color', 'muser_base'),
    'TEXT_COLOR' => $form_values['custom_text_color']['#value'] ?? theme_get_setting('custom_text_color', 'muser_base'),
    'MESSAGE_ERROR_BG' => $form_values['custom_error_message_color']['#value'] ?? theme_get_setting('custom_error_message_color', 'muser_base'),
    'MESSAGE_WARNING_BG' => $form_values['custom_warning_message_color']['#value'] ?? theme_get_setting('custom_warning_message_color', 'muser_base'),
    'MESSAGE_STATUS_BG' => $form_values['custom_status_message_color']['#value'] ?? theme_get_setting('custom_status_message_color', 'muser_base'),
    'MESSAGE_ERROR_TEXT' => $form_values['custom_message_error_text_color']['#value'] ?? theme_get_setting('custom_message_error_text_color', 'muser_base'),
    'MESSAGE_WARNING_TEXT' => $form_values['custom_message_warning_text_color']['#value'] ?? theme_get_setting('custom_message_warning_text_color', 'muser_base'),
    'MESSAGE_STATUS_TEXT' => $form_values['custom_message_status_text_color']['#value'] ?? theme_get_setting('custom_message_status_text_color', 'muser_base'),
    'TEXT_OVER_PRIMARY' => $form_values['custom_text_over_primary_color']['#value'] ?? theme_get_setting('custom_text_over_primary_color', 'muser_base'),
    'TEXT_OVER_SECONDARY' => $form_values['custom_text_over_secondary_color']['#value'] ?? theme_get_setting('custom_text_over_secondary_color', 'muser_base'),
  ];

  //    if ($colorset_settings['accent_color_advanced_custom']) {
  //      $colorset['light'] = $colorset_settings['accent_color_light'];
  //      $colorset['text'] = $colorset_settings['accent_color_text'];
  //    }
  //    else {
  //      $colorset['light'] = muser_base_lighten_darken_color($colorset_settings['accent_color_base'], $colorset_settings['accent_color_light_change_factor']);
  //      $colorset['text'] = muser_base_lighten_darken_color($colorset_settings['accent_color_base'], $colorset_settings['accent_color_text_change_factor'] * -1);
  //    }

  return $colorset;

}

function muser_base_create_accent_css($values = NULL) {
  if ($values) {
    $colorset = muser_base_get_accent_colors($values['colorset']['#value'], $values);
  }
  else {
    $colorset = muser_base_get_accent_colors();
  }
  $theme_folder = drupal_get_path('theme', 'muser_base');

  $css = file_get_contents($theme_folder . '/css/variables.css');
  $css = preg_replace('/PRIMARY_COLOR/', $colorset['PRIMARY_COLOR'], $css);
  $css = preg_replace('/SECONDARY_COLOR/', $colorset['SECONDARY_COLOR'], $css);
  $css = preg_replace('/BACKGROUND_COLOR/', $colorset['BACKGROUND_COLOR'], $css);
  $css = preg_replace('/TITLE_COLOR/', $colorset['TITLE_COLOR'], $css);
  $css = preg_replace('/TEXT_COLOR/', $colorset['TEXT_COLOR'], $css);
  $css = preg_replace('/MESSAGE_ERROR_BG/', $colorset['MESSAGE_ERROR_BG'], $css);
  $css = preg_replace('/MESSAGE_WARNING_BG/', $colorset['MESSAGE_WARNING_BG'], $css);
  $css = preg_replace('/MESSAGE_STATUS_BG/', $colorset['MESSAGE_STATUS_BG'], $css);
  $css = preg_replace('/MESSAGE_ERROR_TEXT/', $colorset['MESSAGE_ERROR_TEXT'], $css);
  $css = preg_replace('/MESSAGE_WARNING_TEXT/', $colorset['MESSAGE_WARNING_TEXT'], $css);
  $css = preg_replace('/MESSAGE_STATUS_TEXT/', $colorset['MESSAGE_STATUS_TEXT'], $css);
  $css = preg_replace('/TEXT_OVER_PRIMARY/', $colorset['TEXT_OVER_PRIMARY'], $css);
  $css = preg_replace('/TEXT_OVER_SECONDARY/', $colorset['TEXT_OVER_SECONDARY'], $css);
  $site_css_dir = 'public://site-css/';

  $file_system = \Drupal::service('file_system');
  $file_system->prepareDirectory($site_css_dir, FileSystemInterface::CREATE_DIRECTORY);
  $file_system->saveData($css, $site_css_dir . 'site-variables.css', TRUE);
}

//:root {
//  --primary: PRIMARY_COLOR;
//  --secondary: SECONDARY_COLOR;
//  --background-color: BACKGROUND_COLOR;
//
//  --title-color: TITLE_COLOR;
//  --text-color: TEXT_COLOR;
//}

function muser_base_hex_to_rgb($hex_code) {
  $hex_code = substr($hex_code, 1);
  if (strlen($hex_code) === 6) {
    list($r, $g, $b) = [
      $hex_code[0] . $hex_code[1],
      $hex_code[2] . $hex_code[3],
      $hex_code[4] . $hex_code[5],
    ];
  }
  elseif (strlen($hex_code) == 3) {
    list($r, $g, $b) = [
      $hex_code[0] . $hex_code[0],
      $hex_code[1] . $hex_code[1],
      $hex_code[2] . $hex_code[2],
    ];
  }
  else {
    return FALSE;
  }
  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);
  return $r . ', ' . $g . ', ' . $b;
}

function muser_base_handle_form_submit($form, FormStateInterface $form_state) {
  muser_base_create_accent_css($form);
  drupal_flush_all_caches();
}

function muser_base_rebuild() {
  muser_base_create_accent_css();
}


function muser_base_lighten_darken_color($color, $factor) {
  $factor = $factor * 100;
  $use_pound = FALSE;
  $color_array = str_split($color);
  if ($color_array[0] === "#") {
    array_shift($color_array);
    $use_pound = TRUE;
  }

  $bin_color = base_convert(implode('', $color_array), 16, 10);

  $red = ($bin_color >> 16) + $factor;

  if ($red > 255) {
    $red = 255;
  }
  else {
    if ($red < 0) {
      $red = 0;
    }
  }

  $blue = (($bin_color >> 8) & 0x00FF) + $factor;

  if ($blue > 255) {
    $blue = 255;
  }
  else {
    if ($blue < 0) {
      $blue = 0;
    }
  }

  $green = ($bin_color & 0x0000FF) + $factor;

  if ($green > 255) {
    $green = 255;
  }
  else {
    if ($green < 0) {
      $green = 0;
    }
  }

  $new_color = ($use_pound ? "#" : "") . base_convert(($green | ($blue << 8) | ($red << 16)), 10, 16);
  return $new_color;
}
