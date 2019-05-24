<?php
namespace Drupal\muser_project\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Checks access for displaying View to disallow the user access if the view is not for their UID.
 */
class CurrentUserAccessCheck implements AccessInterface {

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

    $uid = $route_match->getParameters()->get('user');
    if (!$tab_user = User::load($uid)) {
      return AccessResult::forbidden();
    }
    $route_name = $route_match->getRouteName();
    $bits = explode('.', $route_name);

    $required_roles = array('administrator', 'mentor', 'site_admin');

    $permission = 'access content';
    if ($bits[1] == 'my_projects') {
      $permission = 'administer project rounds';
    }
    elseif ($bits[1] == 'applications') {
      $permission = 'administer user applications';
    }
    elseif ($bits[1] == 'my_favorites') {
      // In practice this permission won't be used, since you need to be
      // a student to have the admin access checked.
      $permission = 'administer user applications';
      $required_roles = array('student');
    }

    // Check if the view param matches this user.
    if (array_intersect($tab_user->getRoles(), $required_roles) && ($uid == $account->id() || $account->hasPermission($permission))) {
      // In a req access check this is equivalent to neutral.
      return AccessResult::allowed();
    }

    // Deny Access.
    return AccessResult::forbidden();
  }

}
