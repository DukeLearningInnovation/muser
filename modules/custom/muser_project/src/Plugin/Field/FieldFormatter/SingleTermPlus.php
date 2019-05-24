<?php

namespace Drupal\muser_project\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'single_term_plus' formatter.
 *
 * @FieldFormatter(
 *   id = "single_term_plus",
 *   label = @Translation("Single term plus"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SingleTermPlus extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'item_to_show' => 'random',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['item_to_show'] = [
      '#type' => 'radios',
      '#title' => t('Item to show'),
      '#description' => t('Which single item to display.'),
      '#options' => [
        'first' => t('First'),
        'last' => t('Last'),
        'random' => t('Random'),
      ],
      '#default_value' => $this->getSetting('item_to_show'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();
    $summary[] = $this->t('Shows a single term along with "+X" text.');

    switch ($settings['item_to_show']) {
      case 'first':
        $summary[] = t('Showing the first item');
        break;
      case 'last':
        $summary[] = t('Showing the last item');
        break;
      case 'random':
      default:
        $summary[] = t('Showing a random item');
    }

    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // $parent_entity = $items->getEntity();

    $elements = [];

    if (!$terms = $this->getEntitiesToView($items, $langcode)) {
      return $elements;
    }

    $settings = $this->getSettings();
    $item_to_show = $settings['item_to_show'] ?? 'random';

    if (count($items) > 1) {
      // We got more than one-- show a single one only.
      $class = 'multiple-terms';
      $additional_count = count($items) - 1;
      switch ($item_to_show) {
        case 'first':
          $index = 0;
          break;
        case 'last':
          $index = count($terms) - 1;
          break;
        case 'random':
        default:
          $index = array_rand($terms, 1);
          break;
      }
      $term = $terms[$index];
      $extra_terms = [];
      foreach ($terms as $extra_term) {
        $extra_terms[] = '<span class="extra-term" data-extra-term-id="' . $extra_term->id() . '">'
          . $this->getTermMarkup($extra_term, Html::escape($extra_term->label()))
          . '</span>';
      }
      $text = '<span class="active-term">' . $this->t('<span class="active-term__term">@term</span> <span class="active-term__count">(+@count)</span>', [
        '@term' => $term->label(),
        '@count' => $additional_count,
      ]);
      $text .= '</span>';
      $text .= '<span class="additional-terms tooltip tooltip--top-right">'
        . '<span class="tooltip__content-wrapper">'
//        . '<span class="tooltip__bridge"></span>'
        . '<span class="tooltip__content">'
        . '<div class="additional-terms-extra">'
        . implode('', $extra_terms)
        . '</div></span></span></span>';
    }
    else {
      // We've only got one.
      $class = 'single-term';
      $additional_count = 0;
      $term = $terms[0];
      $text = Html::escape($term->label());
    }

    $elements[] = [
      '#markup' => $this->getTermMarkup($term, $text, $class, $additional_count),
    ];

    return $elements;

  }

  /**
   * Return icon and term name markup.
   *
   * @param $term
   * @param $text
   * @param null $class
   * @param bool $multiple
   *
   * @return string
   */
  protected function getTermMarkup($term, $text, $class = NULL, $multiple = FALSE) {

    $icon = '';
    $term_fields = $term->getFieldDefinitions();
    if (!empty($term_fields['field_icon'])) {
      // Term entity uses an icon.
      if ($multiple) {
        // Got multiple-- use tags icon.
        $icon = 'fas fa-tags';
      }
      else {
        // Only one-- use term's icon.
        if (!$icon = $term->field_icon->value) {
          $icon = 'fas fa-tag';
        }
      }
      $icon = $this->t('<i class="@icon"></i>', ['@icon' => $icon]);
    }

    return $icon
      . '<span class="term ' . $class . '">'
      . $text
      . '</span>';

  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for taxonomy terms.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
  }

}
