<?php

/**
 * @file
 * Contains \Drupal\relation\RelationTypeInterface.
 */

namespace Drupal\relation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Relation Type entity.
 */
interface RelationTypeInterface extends ConfigEntityInterface {
  /**
   * Get valid entity/bundle pairs that can be associated with this type
   * of Relation.
   *
   * @param NULL|string $direction
   *   Bundle direction. Leave as NULL to get all.
   *
   * @return array
   *   An array containing bundles as key/value pairs, keyed by entity type.
   */
  public function getBundles($direction = NULL);

  /**
   * Returns a reversed label of this relation type.
   *
   * @return string
   *   A reversed label of this relation type.
   */
  public function reverseLabel();

}
