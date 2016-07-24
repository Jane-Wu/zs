<?php

/**
 * @file
 * Contains \Drupal\flag\Controller\ActionLinkController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\relation\Entity\Relation;
use Drupal\Core\Render\Element;

use Drupal\capital_news\FavoriteNewsLink;
use Drupal\relation\Entity\RelationType;

/**
 * Provides a controller to flag and unflag when routed from a normal link.
 */
class NewsLinkController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Performs a flagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object.
   *
   * @see \Drupal\flag\Plugin\Reload
   */
  public function favorite($nid, Request $request) {
    $relation =  new FavoriteNewsLink($nid);
    $rids = $relation->exists();

    if(empty($rids)){
      $relation->create();
      // Save the news to local
      save_news_content($nid);
      return $this->generateResponse($request, true, $relation);
    } else{
      $relation->remove();
      return $this->generateResponse($request, false, $relation);
    }
  }
}

