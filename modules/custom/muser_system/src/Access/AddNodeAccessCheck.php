<?php
namespace Drupal\muser_system\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;

/**
 * Checks access for displaying link to node add form.
 */
class AddNodeAccessCheck implements AccessInterface {

  /**
   * A custom access check for bundle type.
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
    /** @var \Drupal\node\Entity\NodeType $bundle */
    $bundle = $route_match->getParameters()->get('node_type');
    if ($bundle->id() == 'project_round') {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission($account, 'create ' . $bundle->id() . ' content');
  }

}
