<?php

namespace Drupal\muser_project\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'status_with_icon' formatter.
 *
 * @FieldFormatter(
 *   id = "status_with_icon",
 *   label = @Translation("Status with icon"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class StatusWithIcon extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Shows status value with Font Awesome icon.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if ($items) {
      /** @var \Drupal\options\Plugin\Field\FieldType\ListStringItem $item */
      foreach ($items as $item) {
        $value = $item->getValue();
        if (empty($value['value'])) {
          continue;
        }
        $elements[] = [
          '#markup' => muser_project_get_application_status_display($value['value'], MUSER_MENTOR),
        ];
      }
    }
    return $elements;
  }

}
