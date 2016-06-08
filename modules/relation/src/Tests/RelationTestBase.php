<?php

namespace Drupal\relation\Tests;

use Drupal\relation\Entity\Relation;
use Drupal\simpletest\WebTestBase;
use Drupal\relation\Entity\RelationType;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class RelationTestBase extends WebTestBase {

  /**
   * Load all dependencies since d.o testbot is fussy.
   */
  public static $modules = [
    'node',
    'relation',
    'relation_endpoint',
    'field',
    'field_ui',
    'block',
    'relation_dummy_field',
  ];

  protected $sleep = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Defines entities.
    $this->createRelationNodes();
    $this->createRelationUsers();

    // Defines relation types.
    $this->createRelationTypes();

    // Defines end points.
    $this->createRelationEndPoints();

    // Defines relations.
    $this->createRelationSymmetric();
    $this->createRelationDirectional();
    $this->createRelationOctopus();
    $this->createRelationUnary();
  }

  /**
   * Creates nodes.
   */
  protected function createRelationNodes() {
    $this->node1 = $this->drupalCreateNode([
      'type' => 'article',
      'promote' => 1,
      'title' => 'Grandparent',
    ]);
    $this->node2 = $this->drupalCreateNode([
      'type' => 'article',
      'promote' => 0,
    ]);
    $this->node3 = $this->drupalCreateNode([
      'type' => 'page',
      'promote' => 1,
      'title' => 'Parent',
    ]);
    $this->node4 = $this->drupalCreateNode([
      'type' => 'page',
      'promote' => 0,
      'title' => 'Child',
    ]);
    $this->node5 = $this->drupalCreateNode([
      'type' => 'page',
      'promote' => 0,
    ]);
    $this->node6 = $this->drupalCreateNode([
      'type' => 'page',
      'promote' => 0,
      'title' => 'Unrelated',
    ]);
  }

  /**
   * Create users for relation tests.
   */
  protected function createRelationUsers() {
    $this->user1 = $this->drupalCreateUser();
  }

  /**
   * Creates end points.
   */
  protected function createRelationEndPoints() {
    $this->endpoints = [
      ['entity_type' => 'node', 'entity_id' => $this->node1->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node4->id()],
    ];
    $this->endpoints_4 = [
      ['entity_type' => 'node', 'entity_id' => $this->node1->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node2->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node3->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node4->id()],
    ];
    $this->endpoints_entitysame = [
      ['entity_type' => 'node', 'entity_id' => $this->node3->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node4->id()],
    ];
    $this->endpoints_entitydifferent = [
      ['entity_type' => 'user', 'entity_id' => $this->user1->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node3->id()],
    ];
    $this->endpoints_unary = [
      ['entity_type' => 'node', 'entity_id' => $this->node5->id()],
    ];
  }

  /**
   * Creates a set of standard relation types.
   */
  protected function createRelationTypes() {
    $this->relation_types['symmetric'] = [
      'id' => 'symmetric',
      'label' => 'symmetric',
      'source_bundles' => [
        'node:article',
        'node:page',
        'taxonomy_term:*',
        'user:*',
      ],
    ];
    $this->relation_types['directional'] = [
      'id' => 'directional',
      'label' => 'directional',
      'reverse_label' => 'reverse_directional',
      'directional' => TRUE,
      'source_bundles' => ['node:*'],
      'target_bundles' => ['node:page'],
    ];
    $this->relation_types['directional_entitysame'] = [
      'id' => 'directional_entitysame',
      'label' => 'directional_entitysame',
      'directional' => TRUE,
      'source_bundles' => ['node:page'],
      'target_bundles' => ['node:page'],
    ];
    $this->relation_types['directional_entitydifferent'] = [
      'id' => 'directional_entitydifferent',
      'label' => 'directional_entitydifferent',
      'directional' => TRUE,
      'source_bundles' => ['user:*'],
      'target_bundles' => ['node:page'],
    ];
    $this->relation_types['octopus'] = [
      'id' => 'octopus',
      'label' => 'octopus',
      'min_arity' => 3,
      'max_arity' => 5,
      'source_bundles' => ['node:article', 'node:page'],
    ];
    $this->relation_types['unary'] = [
      'id' => 'unary',
      'label' => 'unary',
      'min_arity' => 1,
      'max_arity' => 1,
      'source_bundles' => ['node:page'],
    ];
    foreach ($this->relation_types as $values) {
      $relation_type = RelationType::create($values);
      $relation_type->save();
    }
  }

  /**
   * Creates a Symmetric relation.
   */
  protected function createRelationSymmetric() {
    // Article 1 <--> Page 4
    $this->relation_type_symmetric = $this->relation_types['symmetric']['id'];
    $this->relation_id_symmetric = $this->saveRelation($this->relation_type_symmetric, $this->endpoints);
  }

  /**
   * Creates a Directional relation.
   */
  protected function createRelationDirectional() {
    // Article 1 --> Page 3
    $this->endpoints_directional = $this->endpoints;
    $this->endpoints_directional[1]['entity_id'] = $this->node3->id();
    $this->endpoints_directional[1]['r_index'] = 1;
    $this->relation_type_directional = $this->relation_types['directional']['id'];
    $this->relation_id_directional = $this->saveRelation($this->relation_type_directional, $this->endpoints_directional);

    // Page 3 --> Page 4
    $this->endpoints_directional2 = $this->endpoints;
    $this->endpoints_directional2[0]['entity_id'] = $this->node3->id();
    $this->endpoints_directional2[1]['entity_id'] = $this->node4->id();
    $this->saveRelation($this->relation_type_directional, $this->endpoints_directional2);

    // Page 3 --> Page 4
    $this->endpoints_entitysame[1]['r_index'] = 1;
    $this->relation_type_directional_entitysame = $this->relation_types['directional_entitysame']['id'];
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);
    // Page 3 --> Page 5
    $this->endpoints_entitysame[1]['entity_id'] = $this->node5->id();
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);
    // Page 4 --> Page 3
    $this->endpoints_entitysame[0]['entity_id'] = $this->node4->id();
    $this->endpoints_entitysame[1]['entity_id'] = $this->node3->id();
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);

    // User 1 --> Page 3
    $this->endpoints_entitydifferent[1]['r_index'] = 1;
    $this->relation_type_directional_entitydifferent = $this->relation_types['directional_entitydifferent']['id'];
    $this->saveRelation($this->relation_type_directional_entitydifferent, $this->endpoints_entitydifferent);
    // User 1 --> Page 4
    $this->endpoints_entitydifferent[1]['entity_id'] = $this->node4->id();
    $this->saveRelation($this->relation_type_directional_entitydifferent, $this->endpoints_entitydifferent);
  }

  /**
   * Creates an Octopus (4-ary) relation.
   */
  protected function createRelationOctopus() {
    // Nodes 1, 2, 3, 4 are related.
    $this->relation_type_octopus = $this->relation_types['octopus']['id'];
    $this->relation_id_octopus = $this->saveRelation($this->relation_type_octopus, $this->endpoints_4);
  }

  /**
   * Creates an Unary relation.
   */
  protected function createRelationUnary() {
    // Page 5 <--> Page 5
    $this->relation_type_unary = $this->relation_types['unary']['id'];
    $this->relation_id_unary = $this->saveRelation($this->relation_type_unary, $this->endpoints_unary);
  }

  /**
   * Saves a relation.
   *
   * @param string $relation_type
   *   Machine name of the relation type.
   * @param array $endpoints
   *   An array containing the endpoints.
   *
   * @return string|int|null
   */
  protected function saveRelation($relation_type, array $endpoints) {
    $relation = Relation::create(array('relation_type' => $relation_type));
    $relation->endpoints = $endpoints;
    $relation->save();
    if ($this->sleep) {
      sleep(1);
    }
    return $relation->id();
  }

}
