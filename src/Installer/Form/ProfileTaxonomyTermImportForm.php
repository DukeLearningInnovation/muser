<?php

namespace Drupal\muser\Installer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides the site configuration form.
 */
class ProfileTaxonomyTermImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'muser_taxonomy_term_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#title'] = $this->t('Import taxonomy terms');

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    if (!$data = $this->getCsvData()) {
      \Drupal::messenger()->addError($this->t('Could not read taxonomy terms.'));
      $form['actions']['save']['#value'] = $this->t('Continue');
      return $form;
    }

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('These terms will be imported initially. You can always manage them later.<br/>Enter one term per line.'),
    ];

    $form['#vocabularies'] = [];

    // Create text areas for each vocabulary with values filled in.
    foreach ($data as $vid => $terms) {

      $vocabulary = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vid);
      $form['#vocabularies'][] = $vid;

      $form['terms_' . $vid] = [
        '#type' => 'textarea',
        '#title' => $vocabulary->label(),
        '#default_value' => implode("\n", $terms),
        '#description' => $vocabulary->getDescription(),
        '#rows' => count($terms) + 3,
      ];
      if ($vid == 'categories') {
        $form['terms_' . $vid]['#description'] .= '<br/>' . $this->t('Format: "Name", "Fontawesome icon (optional)"');
      }
    } // Loop thru vocabularies.

    return $form;

  }

  /**
   * @return array|bool
   */
  private function getCsvData() {

    $path = drupal_get_path('profile', 'muser') . '/defaults/terms.csv';

    if (!file_exists($path) || !is_readable($path)) {
      return FALSE;
    }

    $header = NULL;
    $data = [];

    $n = 0;
    if (($handle = fopen($path, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 1000)) !== FALSE) {
        $n++;
        if ($n == 1) {
          // Skip header row.
          continue;
        }
        if (!empty($row[1])) {
          $line = $row[1];
          if (!empty($row[3])) {
            if (strpos($line, ',') !== FALSE) {
              $line = '"' . str_replace('"', '\"', $line) . '"';
            }
            $line .= ',' . $row[3];
          } // Got an icon?
          $data[$row[0]][] = $line;
        }
      }
      fclose($handle);
    }

    return $data;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $count = 0;
    $vocabularies = $form['#vocabularies'];

    foreach ($vocabularies as $vocabulary) {

      $value = $form_state->getValue('terms_' . $vocabulary);
      if (!trim($value)) {
        continue;
      }
      if (!$terms = explode("\n", $value)) {
        continue;
      }

      $weight = 0;
      foreach ($terms as $name) {
        $icon = NULL;
        if ($vocabulary == 'categories') {
          $row = str_getcsv($name);
          $name = ($row[0]) ?? NULL;
          $icon = ($row[1]) ?? NULL;
        }
        if (!$name = trim($name)) {
          continue;
        }
        // Create term.
        $term = Term::create([
          'vid' => $vocabulary,
          'uid' => 1,
          'name' => $name,
          'weight' => $weight,
        ]);
        if (!empty($icon)) {
          $term->field_icon->value = $icon;
        }
        $term->save();
        $weight++;
        $count++;
      } // Loop thru terms.

    } // Loop thru vocabularies.

    \Drupal::messenger()->addMessage($this->t('Created %count taxonomy terms.', ['%count' => $count]));

  } // End submitForm().

}
