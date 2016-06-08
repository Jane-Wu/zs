<?php

/**
 * @file
 * Contains \Drupal\capital_views\Plugin\views\field\CompanyStaff.
 */

namespace Drupal\capital_news\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\capital_news\FavoriteNewsLink;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("favorite_link")
 */
class NewsFavoriteLinkField extends FieldPluginBase {
  public function query() {
  }
  public function render(ResultRow $values) {
    $nid = $values->_entity->id();
    $relation = new FavoriteNewsLink($nid);
    if(isset($values->_relationship_entities['relation_user_favorite_news_user']) && 
      $values->_relationship_entities['relation_user_favorite_news_user']->id() == \Drupal::currentUser()->id()){
        $link = $relation->getRemoveLink();
        return $this->getRenderer()->render($link);
      }
    $link = $relation->getAddLink();
    return $this->getRenderer()->render($link);
  }
}

