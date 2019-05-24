<?php

namespace Drupal\muser_project\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Defines custom access control handler for the Muser project-related entities.
 */
class UpdateFlaggingStatusAccess implements AccessInterface {

  /**
   * Determine if user can update status of Flagging.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(AccountInterface $account, FlaggingInterface $flagging) {
    if (muser_project_allow_flagging_access('update_status', $flagging, $account)) {
      return AccessResult::allowed()
        ->addCacheableDependency($flagging);
    }
    return AccessResult::forbidden()
      ->addCacheableDependency($flagging);
  }

}
