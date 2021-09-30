<?php

namespace Drupal\muser_project;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Lazy-builder callback.
 */
class GetApplicationCountText implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['lazyBuilder'];
  }

  /**
   * Returns application count text.
   *
   * @param int $nid
   *   Project-Round nid.
   * @param int $project_nid
   *   Project nid.
   */
  public static function lazyBuilder(int $nid, int $project_nid) {
    return [
      '#cache' => [
        'tags' => ['project_application_count:' . $project_nid],
      ],
      '#markup' => ' | <span class="applicants">'
      . t('@applicants appl.', ['@applicants' => muser_project_get_application_count($nid)])
      . '</span>',
    ];
  }

}
