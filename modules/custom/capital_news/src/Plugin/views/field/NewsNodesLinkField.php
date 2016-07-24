<?php

/**
 * @file
 * Contains \Drupal\capital_views\Plugin\views\field\CompanyStaff.
 */

namespace Drupal\capital_news\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\capital_news\LinkNewsNodeLink;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("linknode_link")
 */
class NewsNodesLinkField extends FieldPluginBase {
  public function query() {
  }
  public function render(ResultRow $values) {
    $nid = $values->_entity->id();
    $relation = new LinkNewsNodeLink($nid);
    $link = $relation->getLink();
    return $this->getRenderer()->render($link);
  }
}

