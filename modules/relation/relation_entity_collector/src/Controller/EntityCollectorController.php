<?php

/**
 * @file
 * Contains \Drupal\relation_entity_collector\Controller\EntityCollectorController.
 */

namespace Drupal\relation_entity_collector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\relation\RelationInterface;

/**
 * Returns responses for Relation routes.
 */
class EntityCollectorController extends ControllerBase {

  /**
   * Page callback copying a relation into SESSION.
   *
   * @param RelationInterface $relation
   *   A relation entity.
   */
  function load(RelationInterface $relation) {
    $_SESSION['relation_edit'] = $relation;
    $_SESSION['relation_type'] = $relation->relation_type;
    $_SESSION['relation_entity_keys'] = array();
    foreach ($relation->endpoints[Language::LANGCODE_NOT_SPECIFIED] as $delta => $endpoint) {
      $storage_handler = \Drupal::entityTypeManager()->getStorage($endpoint['entity_type']);
      $entities = $storage_handler->loadMultiple(array($endpoint['entity_id']));
      $entity = $entities[$endpoint['entity_id']];
      list(, , $entity_bundle) = entity_extract_ids($endpoint['entity_type'], $entity);
      $_SESSION['relation_entity_keys'][] = array(
        'entity_type' => $endpoint['entity_type'],
        'entity_id' => $endpoint['entity_id'],
        'entity_bundle' => $entity_bundle,
        'r_index' => $delta,
        'entity_label' => "$entity_bundle: " . entity_label($endpoint['entity_type'], $entity),
        'entity_key' => $endpoint['entity_type'] . ':' . $endpoint['entity_id'],
      );
    }
    drupal_set_message(t('The relation is ready for edit'));
    drupal_goto();
  }

}
