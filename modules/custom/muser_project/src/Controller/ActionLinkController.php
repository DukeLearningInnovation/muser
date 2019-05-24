<?php

namespace Drupal\muser_project\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\muser_project\Ajax\UpdateProjectStatusCommand;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Controller responses to flag and unflag action links.
 *
 * If the action_link is a normal link then after an update the response to a
 *  valid request is a redirect to the entity with drupal update message.
 *
 * For an ajax_action_link the response is a set of AJAX commands to update the
 * link in the page. If the user agent has javascript disabled then the
 * behaviour reverts to that of a normal link.
 */

class ActionLinkController implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }


  public function activate($entity_id, Request $request) {

    $entity = Node::load($entity_id);

    // Current Round
    $round = muser_project_get_current_round();
    if (!$round) {
      return $this->generateResponse(FALSE, $entity, $request, 'There is no current round.');
    }

    $pr = muser_project_get_project_round_for_project($entity->id(), $round);
    if ($pr) {
      return $this->generateResponse(TRUE, $entity, $request, t('Project is already in the current round.'));
    }

    if (!muser_project_add_project_to_round($entity->id(), $round)) {
      return $this->generateResponse(FALSE, $entity, $request, t('Problem adding project to the current round.'));
    }

    return $this->generateResponse(TRUE, $entity, $request, t('Project activated in current round.'));
  }

  public function inactivate($entity_id, Request $request) {
    $entity = Node::load($entity_id);

    // Current Round
    $round = muser_project_get_current_round();
    if (!$round) {
      return $this->generateResponse(TRUE, $entity, $request, 'There is no current round.');
    }

    $pr = muser_project_get_project_round_for_project($entity->id(), $round);
    if (!$pr) {
      return $this->generateResponse(FALSE, $entity, $request, t('Project is already not the current round.'));
    }

    if (!muser_project_remove_project_from_round($pr)) {
      return $this->generateResponse(TRUE, $entity, $request, t('Problem removing @label from the current round.'));
    }

    return $this->generateResponse(FALSE, $entity, $request, t('Project inactivated in current round.'));
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $message
   *   (optional) The message to flash.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object.
   */
  protected function generateResponse($active = FALSE, EntityInterface $entity, Request $request, $message = NULL) {
    if ($request->get(MainContentViewSubscriber::WRAPPER_FORMAT) == 'drupal_ajax') {
      // Create a new AJAX response.
      $response = new AjaxResponse();

      if (!$active) {
        $url = \Drupal\Core\Url::fromRoute('muser_project.action_activate_project', array('entity_id' => $entity->id()));
        $label = 'Activate';
        $status_class = 'project--inactive';
      } else {
        $url = \Drupal\Core\Url::fromRoute('muser_project.action_inactivate_project', array('entity_id' => $entity->id()));
        $label = 'Inactivate';
        $status_class = 'project--active';
      }
      $url->setRouteParameter('destination', \Drupal::destination()->get());

      $build = [
        '#theme' => 'ajax_link_activate',
        '#title' => t($label),
        '#action' => 'activate',
        '#attributes' => [
          'class' => ['use-ajax'],
        ],
        '#project' => $entity,
        '#attached' => [
          'library' => ['core/drupal.dialog.ajax'],
        ],
      ];

      // Bubble to CSRF
      $rendered_url = $url->toString(TRUE);
      $rendered_url->applyTo($build);

      $build['#attributes']['href'] = $rendered_url->getGeneratedUrl();

      // Generate a CSS selector to use in a JQuery Replace command.
      $selector = '.project-activate-' . $entity->id();

      // Create a new JQuery Replace command to update the link display.
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($build));
      $response->addCommand($replace);

      $updateClass = new UpdateProjectStatusCommand($selector, $status_class);
      $response->addCommand($updateClass);

      // The bleow will show a confirmation message flashing next to the link if uncommented.
//      if ($message) {
//        // Push a message pulsing command onto the stack.
//        $pulse = new ActionLinkFlashCommand($selector, $message);
//        $response->addCommand($pulse);
//      }
    }
    elseif ($entity->hasLinkTemplate('canonical')) {
      // Redirect back to the entity. A passed in destination query parameter
      // will automatically override this.
      $url_info = $entity->toUrl();

      $options['absolute'] = TRUE;
      $url = Url::fromRoute($url_info->getRouteName(), $url_info->getRouteParameters(), $options);
      $response = new RedirectResponse($url->toString());

    }
    else {
      // For entities that don't have a canonical URL (like paragraphs),
      // redirect to the front page.
      $front = Url::fromUri('internal:/');
      $response = new RedirectResponse($front);
    }

    return $response;
  }

}
