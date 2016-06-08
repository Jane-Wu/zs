<?php

namespace Drupal\relation;

/**
 * Relation Repository Interface.
 */
interface RelationRepositoryInterface {

  /**
   * Returns the relation types that can have the given entity as an endpoint.
   *
   * @param string $entity_type
   *   The entity type of the endpoint.
   * @param string $bundle
   *   The bundle of the endpoint.
   * @param string $endpoint
   *   (optional) the type of endpoint. This is only used for directional
   *   relation types. Possible options are 'source', 'target', or 'both'.
   *
   * @return array
   *   An array of relation types
   */
  public function getAvailable($entity_type, $bundle, $endpoint = 'source');

  /**
   * Checks if a relation exists.
   *
   * The following example demonstrates how to check if a relation of type
   * 'likes' exists between two entities, user 17 and node 253.
   *
   * @code
   *   $endpoints = array(
   *     array('entity_type' => 'user', 'entity_id' => 17),
   *     array('entity_type' => 'node', 'entity_id' => 253),
   *   );
   *   $relation_type = 'likes';
   *   $results = Relation::relationExists($endpoints, $relation_type);
   * @endcode
   *
   * @param array $endpoints
   *   An array containing endpoints. Each endpoint is an array with keys
   *   'entity_type' and 'entity_id'. The keys of each endpoint correspond to
   *   'r_index' if $enforce_direction is TRUE.
   * @param string $relation_type
   *   (Optional) The relation type (bundle) of the relation to be checked.
   * @param bool $enforce_direction
   *   (Optional) Whether to enforce direction as specified in $endpoints.
   *
   * @return array
   *   Array of Relation ID's keyed by revision ID.
   */
  public function relationExists(array $endpoints, $relation_type = NULL, $enforce_direction = FALSE);

}
