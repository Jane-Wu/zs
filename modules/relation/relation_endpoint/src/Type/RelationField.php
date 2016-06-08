<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Type\RelationField.
 */
namespace Drupal\relation_endpoint\Type;

use Drupal\Core\Field\FieldItemList;
/**
 *
 */
class RelationField extends FieldItemList {
  /**
   *
   */
  public function preSave() {
    // We need r_index here because EntityFieldQuery can't query on deltas.
    if (isset($this->list)) {
      foreach ($this->list as $delta => &$item) {
        $item->r_index = $delta;
      }
    }
  }

}
