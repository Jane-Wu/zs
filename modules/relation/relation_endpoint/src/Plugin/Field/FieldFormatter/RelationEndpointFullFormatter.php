<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Plugin\Field\FieldFormatter\RelationEndpointFullFormatter.
 */

namespace Drupal\relation_endpoint\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "relation_endpoint_full",
 *   label = @Translation("Render endpoints"),
 *   field_types = {
 *     "relation_endpoint"
 *   },
 *   settings = {
 *   }
 * )
 */
class RelationEndpointFullFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];

    foreach ($items as $delta => $item) {
      $storage_handler = \Drupal::entityTypeManager()->getStorage($item->entity_type);
      if ($entity = $storage_handler->load($item->entity_id)) {
        // @todo: allow view mode customisation.
        $build[$delta] = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, 'teaser');
      }
    }

    return $build;
  }

}
