<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsViewController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;

class NewsViewController extends ControllerBase {
  public function content($nid) {
    $node = \Drupal\node\Entity\Node::load($nid);
    if ($node->bundle() == 'news') {
      $content = $node->field_content[0]->view();
      return $content;
    }
    else {
      return array();
    }
  }
}
