<?php

namespace Drupal\muser_project;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Method to return a "cancel" URL for a form.
 */
trait CancelUrlTrait {

  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * Builds the cancel link for a confirmation form.
   *
   * Based on ConfirmFormHelper::buildCancelLink()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $cancel_url
   *   The default url for cancel.
   *
   * @return array
   *   The link render array for the form.
   */
  public function buildCancelLink(Request $request, $cancel_url) {
    // Prepare cancel link.
    $query = $request->query;
    $url = NULL;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      // @todo Revisit this in https://www.drupal.org/node/2418219.
      try {
        $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
        // Suppress the exception and fall back to the form's cancel url.
      }
    }
    // Check for a route-based cancel link.
    if (!$url) {
      $url = $cancel_url;
    }
    return [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
  }

}
