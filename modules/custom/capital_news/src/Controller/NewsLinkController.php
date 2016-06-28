<?php

/**
 * @file
 * Contains \Drupal\flag\Controller\ActionLinkController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\relation\Entity\Relation;
use Drupal\capital_news\FavoriteNewsLink;
use Drupal\Core\Render\Element;

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
      return $this->generateResponse($request, true, $relation);
    } else{
      $relation->remove();
      return $this->generateResponse($request, false, $relation);
    }
  }
  public function link($nid, $news_id, Request $request) {
    $relation =  new LinkNewsNodeLink($nid, $news_id);
    $rids = $relation->exists();

    if(empty($rids)){
      $relation->create();
      //return $this->generateResponse($request, true, $relation);
    } else{
      $relation->remove();
      //return $this->generateResponse($request, false, $relation);
    }
  }

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
  public function listNodes($news_id, Request $request) {
    return $this->getList($request, $news_id);

  }

  /**
   * Generates a response object after handing the un/flag request.
   *
   * Depending on the wrapper format of the request, it will either redirect
   * or return an ajax response.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object.
   */
  protected function getList(Request $request, $news_id){
    if ($request->get(MainContentViewSubscriber::WRAPPER_FORMAT) == 'drupal_ajax') {
      // Create a new AJAX response.
      $response = new AjaxResponse();
      $link_id = '#capital-link-news-' . $news_id;// . $relation->news_id;
      \Drupal::logger('capital-test')->debug(print_r($link_id, true));

      $list = [
        '#type' => 'container',
          '#attributes' => [
            'class' => 'dropdown-toggle',
          ],
        'button' => [
          '#type' => 'dropbutton',
          '#links' => [
            /*
            't' => [
              'title' => '1',
              'url' => Url::fromRoute('capital_news.favoritenews', ['nid' => 121]),
            ],
             */
          ],
        ],
      ];
      
          

      $list = '<div class="dropdown">
        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
        <li><a href="#">test</a></li>
        <li><a href="#">test1</a></li>
        <li role="separator" class="divider"></li>
        <li><a href="#">test3</a></li>
        </ul>
        </div>';
      $replace = new AfterCommand($link_id, $list);//$isNew? $relation->getRemoveLink(): $relation->getAddLink());
      //      $replace = new ReplaceCommand($link_id, 'asdf');//$isNew? $relation->getRemoveLink(): $relation->getAddLink());
      $response->addCommand($replace);

      return $response;
    }
    else {
      //\Drupal::logger('capital-test')->notice(print_r('else', true));
    }
  }
  protected function generateResponse(Request $request, $isNew, $relation) {
    if ($request->get(MainContentViewSubscriber::WRAPPER_FORMAT) == 'drupal_ajax') {
      // Create a new AJAX response.
      $response = new AjaxResponse();
      $link_id = '#capital-favorite-news-' . $relation->news_id;
      $replace = new ReplaceCommand($link_id, $isNew? $relation->getRemoveLink(): $relation->getAddLink());
      $response->addCommand($replace);

      return $response;
    }
    else {
      //\Drupal::logger('capital-test')->notice(print_r('else', true));
    }
  }
}

