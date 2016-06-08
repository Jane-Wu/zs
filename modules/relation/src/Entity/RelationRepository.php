<?php

namespace Drupal\relation\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\relation\RelationRepositoryInterface;

/**
 * Provides mechanism for retrieving available relations types.
 */
class RelationRepository implements RelationRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a new RelationRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailable($entity_type, $bundle, $endpoint = 'source') {
    $bundle_key = $entity_type . ':' . $bundle;
    $all_bundle_key = $entity_type . ':*';
    $available_types = array();
    $relation_types = $this->entityTypeManager->getStorage('relation_type')->loadMultiple();
    foreach ($relation_types as $relation_type) {
      $available = FALSE;
      if ($endpoint == 'source' || $endpoint == 'both') {
        if (in_array($bundle_key, $relation_type->source_bundles) || in_array($all_bundle_key, $relation_type->source_bundles)) {
          $available = TRUE;
        }
      }
      if ($endpoint == 'target' || $endpoint == 'both') {
        if (in_array($bundle_key, $relation_type->target_bundles) || in_array($all_bundle_key, $relation_type->target_bundles)) {
          $available = TRUE;
        }
      }
      if ($available) {
        $available_types[] = $relation_type;
      }
    }

    return $available_types;
  }

  /**
   * {@inheritdoc}
   */
  public function relationExists(array $endpoints, $relation_type = NULL, $enforce_direction = FALSE) {
    $query = $this->entityQuery->get('relation');
    foreach ($endpoints as $r_index => $endpoint) {
      relation_query_add_related($query, $endpoint['entity_type'], $endpoint['entity_id'], $enforce_direction ? $r_index : NULL);
    }
    if ($relation_type) {
      $query->condition('relation_type', $relation_type);
    }
    $query->condition('arity', count($endpoints));

    // If direction of the relation is not forced make sure the each endpoint
    // is counted just once.
    if (!$enforce_direction) {
      $query->addTag('enforce_distinct_endpoints');
    }
    return $query->execute();
  }

}
