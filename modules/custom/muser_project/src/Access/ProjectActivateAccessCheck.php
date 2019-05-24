<?php
namespace Drupal\muser_project\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;

/**
 * Checks access for displaying Activate/Inactivate link on project node.
 */
class ProjectActivateAccessCheck implements AccessInterface {

  /**
   * A custom access check for project activate links.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $entity_id = $route_match->getParameters()->get('entity_id');
    $object = Node::load($entity_id);

    // Check if this is a project entity.
    if ($object->getEntityType()->id() === 'node'  && $object->bundle() == 'project') {
      // Check if the user as edit access to the project
      $access = $object->access('update', $account, TRUE);

      // Check secondary permissions
      if ($access->isAllowed()) {
        // We dont check the administer project override permission here, since this is for the quick toggle link.
        //$account->hasPermission('administer project rounds')) {
        // Check if in posting period.
        $round_nid = muser_project_get_current_round();

        // If not in the posting period then return false
        if ($round_nid && muser_project_round_in_period($round_nid)) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

}