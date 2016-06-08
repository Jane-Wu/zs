<?php

/**
 * @file
 * Contains \Drupal\relation\Controller\RelationController.
 */

namespace Drupal\relation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\relation\RelationInterface;

/**
 * Returns responses for Relation routes.
 */
class RelationController extends ControllerBase {
  /**
   * Displays a relation.
   *
   * @param \Drupal\relation\RelationInterface $relation
   *   The relation to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(RelationInterface $relation) {
    return $this->entityTypeManager()->getViewBuilder('relation')->view($relation);
  }

  /**
   * The _title_callback for the entity.relation.canonical route.
   *
   * @param RelationInterface $relation
   *   A relation entity.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(RelationInterface $relation) {
    return $relation->label();
  }

}
