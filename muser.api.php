<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * Allow modules to alter/add colorsets for the theme.
 *
 * See muser_base_get_colorsets() in muser/themes/muser_base/includes/theme_settings.inc
 *
 * @param array $colorsets
 *   Array of existing colorsets.
 *
 * @return array
 *   Updated array of colorsets.
 */
function hook_muser_colorsets_alter(&$colorsets) {
  // Add a new colorset.
  $colorsets['colorset_dusk'] = [
    'name' => t('Dusk (Gray/Yellow)'),
    'PRIMARY_COLOR' => '#5B6780',
    'SECONDARY_COLOR' => '#EAAA10',
    'BACKGROUND_COLOR' => '#F3F2E1',
    'TITLE_COLOR' => '#603D30',
    'TEXT_COLOR' => '#262626',
    'MESSAGE_ERROR_BG' => '#A6193E',
    'MESSAGE_WARNING_BG' => '#FFD110',
    'MESSAGE_STATUS_BG' => '#B7BF20',
    'MESSAGE_ERROR_TEXT' => '#FFFFFF',
    'MESSAGE_WARNING_TEXT' => '#262626',
    'MESSAGE_STATUS_TEXT' => '#262626',
    'TEXT_OVER_PRIMARY' => '#FFFFFF',
    'TEXT_OVER_SECONDARY' => '#262626',
  ];
  return $colorsets;
}
