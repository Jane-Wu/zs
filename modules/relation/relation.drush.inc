<?php

/**
 * @file
 * Drush integration for the relation module.
 */
use Drupal\relation\Entity\Relation;

use Drupal\relation\Entity\RelationType;

/**
 * Implements hook_drush_command().
 */
function relation_drush_command() {
  $items['relation-generate'] = array(
    'description' => dt('Generates relations.'),
    'arguments' => array(
      'number_relations' => dt('The number of relations to generate.'),
    ),
    'options' => array(
      'kill' => 'Delete all relations before generating new ones.',
      'types' => 'A comma delimited list of relation types to create.',
    ),
    'aliases' => array('genrel'),
  );
  return $items;
}

/**
 * Drush callback to generate relations.
 *
 * @param $number_relations
 *   Number of entities to generate of each entity_type.
 */
function drush_relation_generate($number_relations) {
  $types = drush_get_option('types');
  $types = $types ? explode(',', $types) : array();
  $kill = drush_get_option('kill');
  relation_generate_relations($number_relations, $types, $kill);
}

/**
 * Sends message to drush log, if enabled.
 *
 * @param $message
 *   Text of message.
 */
function relation_generate_message($message) {
  if (function_exists('drush_log')) {
    drush_log($message, 'ok');
  }
  else {
    drupal_set_message($message);
  }
}

/**
 * Generates pseudorandom relations. Appropriate entities must already exist.
 *
 * @param $number_relations
 *   Number of entities to generate of each entity_type.
 * @param $types
 *   Array of relation_type to generate and delete relations. Set to NULL to
 *   generate and delete all.
 * @param $kill
 *   Whether to delete all existing relations before creating new ones.
 *
 * @return
 *   Array of relation IDs of the generated relations.
 */
function relation_generate_relations($number_relations = 10, $relation_types = array(), $kill = FALSE) {
  $relation_types = RelationType::loadMultiple($relation_types);
  if ($kill) {
    foreach ($relation_types as $relation_type) {
      $relation_ids = Drupal::entityQuery('relation')
        ->condition('relation_type', $relation_type->id())
        ->execute();
      $storage_handler = \Drupal::entityTypeManager()->getStorage('relation');
      $relations = $storage_handler->loadMultiple($relation_ids);
      $storage_handler->delete($relations);
      relation_generate_message(t('Deleted all @type relations.', array('@type' => $relation_type->label())));
    }
  }

  $new_relation_ids = array();
  foreach ($relation_types as $relation_type) {
    $available_types = array();
    foreach ($relation_type->source_bundles as $bundle_key) {
      list($entity_type, $bundle) = explode(':', $bundle_key, 2);
      $available_types['source'][$entity_type][] = $bundle;
    }
    foreach ($relation_type->target_bundles as $bundle_key) {
      list($entity_type, $bundle) = explode(':', $bundle_key, 2);
      $available_types['target'][$entity_type][] = $bundle;
    }
    $arity = rand($relation_type->min_arity, $relation_type->min_arity);

    for ($i = $number_relations; $i > 0; $i--) {
      // Start new relation.
      $endpoints = array();
      for ($r_index = 0; $r_index < $arity; $r_index++) {
        $direction = ($relation_type->directional && $r_index > 0) ? 'target' : 'source';
        $entity_type = array_rand($available_types[$direction]);
        $entity_info = \Drupal::entityTypeManager()->getDefinition($entity_type);
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        $query = Drupal::entityQuery($entity_type);

        if (!in_array('*', $available_types[$direction][$entity_type])) {
          // Entities with a single bundle don't support EFQ bundle condition.
          if (count($bundles) > 1 && isset($entity_info['entity_keys']['bundle'])) {
            $query->condition($entity_info['entity_keys']['bundle'], $available_types[$direction][$entity_type], 'IN');
          }
        }

        if ($results = $query->execute()) {
          $key = array_rand($results);
          $endpoints[] = array(
            'entity_type' => $entity_type,
            'entity_id'   => $results[$key],
            'r_index'     => $r_index,
          );
        }
      }
      $relation = Relation::create(array('relation_type' => $relation_type->id()));
      $relation->endpoints = $endpoints;
      $relation->save();
      $new_relation_ids[] = $relation->id();
      relation_generate_message(
        \Drupal::translation()->formatPlural(
          $number_relations,
          'Generated @count @relation_type relation.',
          'Generated @count @relation_type relations.',
          array('@relation_type' => $relation_type->id())
        ), 'ok');
    }
  }
  return $new_relation_ids;
}
