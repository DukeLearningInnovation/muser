<?php

namespace Drupal\muser_system\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MuserSystemAlterRouteSubscriber.
 *
 * @package Drupal\muser_system\Routing
 * Listens to the dynamic route events.
 */
class MuserSystemAlterRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $admin_routes = ['view.rounds.page_1', 'view.administer_projects.page_1'];
    $user_routes = [
      'view.my_projects.page',
      'view.my_favorites.page',
      'view.applications.page_new',
      'view.applications.page_review',
      'view.applications.page_accepted',
      'view.applications.page_rejected',
    ];
    foreach ($collection->all() as $name => $route) {
      if (in_array($name, $admin_routes)) {
        $route->setOption('_admin_route', TRUE);
      }

      // Add additional bundle restriction to just show on projects to these routes.
      if ($name == 'view.administer_projects.page_1') {
        $route->setRequirement('_muser_project_bundle_access_check', 'TRUE');
      }

      // Give the user that matches the user param in the view access
      if (in_array($name, $user_routes)) {
        $route->setRequirement('_muser_project_user_access_check', 'TRUE');
      }

      // Do not link to node add form for project_rounds
      if ($name == 'node.add') {
        $route->setRequirement('_muser_system_node_add_access', 'TRUE');
      }

      // Alter form used for delete multiple
      if ($name == 'entity.node.delete_multiple_form' || $name == 'node.multiple_delete_confirm') {
        $route->setDefault('_form', 'Drupal\muser_system\Form\MuserNodeDeleteMultiple');
      }
    }

  }
}