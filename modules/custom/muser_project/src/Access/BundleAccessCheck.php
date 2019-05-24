<?php
namespace Drupal\muser_project\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;

/**
 * Checks access for displaying View to restrict to a round node.
 */
class BundleAccessCheck implements AccessInterface {

  /**
   * A custom access check for round param.
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

    $entity_id = $route_match->getParameters()->get('node');
    $object = Node::load($entity_id);

    // Check if this is a project entity.
    if ($object && $object->getEntityType()->id() === 'node'  && $object->bundle() == 'round' && $account->hasPermission('administer project rounds')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}