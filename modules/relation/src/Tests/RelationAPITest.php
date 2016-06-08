<?php

namespace Drupal\relation\Tests;
use Drupal\relation\Entity\RelationType;

use Drupal\relation\Entity\Relation;

/**
 * Test general API for Relation.
 *
 * Create nodes, add relations and verify that they are related.
 * This test suite also checks all methods available in RelationQuery.
 *
 * @group Relation
 */
class RelationAPITest extends RelationTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // This is necessary for the ->sort('created', 'DESC') test.
    $this->sleep = TRUE;
    parent::setUp();

    // Defines users and permissions.
    $permissions = [
      // Node.
      'create article content',
      'create page content',
      // Relation.
      'administer relation types',
      'administer relations',
      'access relations',
      'create relations',
      'edit relations',
      'delete relations',
    ];
    $this->web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test relation helper functions.
   */
  public function testRelationHelpers() {
    // ## Test relationExists() method of entity repository relation service.
    // Where relation type is set.
    $exists = $this->container->get('entity.repository.relation')->relationExists($this->endpoints, $this->relation_type_symmetric);
    $this->verbose(print_r($exists, TRUE));
    $this->assertTrue(isset($exists[$this->relation_id_symmetric]), 'Relation exists.');

    // Where relation type is not set.
    $exists = $this->container->get('entity.repository.relation')->relationExists($this->endpoints_4);
    $this->assertTrue(isset($exists[$this->relation_id_octopus]), 'Relation exists.');

    // Where endpoints does not exist.
    $endpoints_do_not_exist = $this->endpoints;
    $endpoints_do_not_exist[1]['entity_type'] = $this->randomMachineName();
    $this->assertEqual(array(), $this->container->get('entity.repository.relation')->relationExists($endpoints_do_not_exist, $this->relation_type_symmetric), 'Relation with non-existant endpoint not found.');

    // Where there are too many endpoints.
    $endpoints_excessive = $this->endpoints;
    $endpoints_excessive[] = ['entity_type' => $this->randomMachineName(), 'entity_id' => 1000];
    $this->assertEqual(array(), $this->container->get('entity.repository.relation')->relationExists($endpoints_do_not_exist, $this->relation_type_symmetric), 'Relation with too many endpoints not found.');

    // Where relation type is invalid.
    $this->assertEqual(array(), $this->container->get('entity.repository.relation')->relationExists($this->endpoints, $this->randomMachineName()), 'Relation with invalid relation type not found.');

  }

  /**
   * Tests all available methods in RelationQuery.
   *
   * Creates some nodes, add some relations and checks if they are related.
   */
  public function testRelationQuery() {
    $relations = Relation::loadMultiple(array_keys(relation_query('node', $this->node1->id())->execute()));

    // Check that symmetric relation is correctly related to node 4.
    $this->assertEqual($relations[$this->relation_id_symmetric]->endpoints[1]->entity_id, $this->node4->id(), 'Correct entity is related: ' . $relations[$this->relation_id_symmetric]->endpoints[1]->entity_id . '==' . $this->node4->id());

    // Symmetric relation is Article 1 <--> Page 4
    // @see https://drupal.org/node/1760026
    $endpoints = [
      ['entity_type' => 'node', 'entity_id' => $this->node4->id()],
      ['entity_type' => 'node', 'entity_id' => $this->node4->id()],
    ];
    $exists = $this->container->get('entity.repository.relation')->relationExists($endpoints, 'symmetric');
    $this->assertTrue(empty($exists), 'node4 is not related to node4.');

    // Get relations for node 1, should return 3 relations.
    $count = count($relations);
    $this->assertEqual($count, 3);

    // Get number of relations for node 4, should return 6 relations.
    $count = relation_query('node', $this->node4->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 6);

    // Get number of relations for node 5, should return 2 relations.
    $count = relation_query('node', $this->node5->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 2);

    // Get relations between entities 2 and 5 (none).
    $query = relation_query('node', $this->node2->id());
    $count = relation_query_add_related($query, 'node', $this->node5->id())
      ->count()
      ->execute();
    $this->assertFalse($count);

    // Get directed relations for node 3 using index, should return 2 relations.
    // The other node 3 relation has an r_index 0.
    $relations = relation_query('node', $this->node3->id(), 1)
      ->execute();
    $this->assertEqual(count($relations), 3);
    $this->assertTrue(isset($relations[$this->relation_id_directional]), 'Got the correct directional relation for nid=3.');

    // Get relations between entities 2 and 3 (octopus).
    $query = relation_query('node', $this->node2->id());
    $relations = relation_query_add_related($query, 'node', $this->node3->id())
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 1);
    // Check that we have the correct relations.
    $this->assertEqual(isset($relations[$this->relation_id_octopus]), 'Got one correct relation.');

    // Get relations for node 1 (symmetric, directional, octopus), limit to
    // directional and octopus with relation_type().
    $relations = relation_query('node', $this->node1->id());
    $or_condition = $relations->orConditionGroup()
      ->condition('relation_type', $this->relation_types['directional']['id'])
      ->condition('relation_type', $this->relation_types['octopus']['id']);
    $relations = $relations->condition($or_condition)
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 2);
    // Check that we have the correct relations.
    $this->assertTrue(isset($relations[$this->relation_id_directional]), 'Got one correct relation.');
    $this->assertTrue(isset($relations[$this->relation_id_octopus]), 'Got a second one.');

    // Get last two relations for node 1.
    $relations = relation_query('node', $this->node1->id())
      ->range(1, 2)
      ->sort('relation_id', 'ASC')
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 2);
    // Check that we have the correct relations.
    $this->assertTrue(isset($relations[$this->relation_id_directional]), 'Got one correct relation.');
    $this->assertTrue(isset($relations[$this->relation_id_octopus]), 'Got a second one.');

    // Get all relations on node 1 and sort them in reverse created order.
    $relations = relation_query('node', $this->node1->id())
      ->sort('created', 'DESC')
      ->execute();
    $this->assertEqual($relations, [
      $this->relation_id_octopus => $this->relation_id_octopus,
      $this->relation_id_directional => $this->relation_id_directional,
      $this->relation_id_symmetric => $this->relation_id_symmetric,
    ]);

    // Create 10 more symmetric relations and verify that the count works with
    // double digit counts as well.
    for ($i = 0; $i < 10; $i++) {
      $this->createRelationSymmetric();
    }
    $count = relation_query('node', $this->node4->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 16);
  }

  /**
   * Tests relation types.
   */
  public function testRelationTypes() {
    // Symmetric.
    $related = relation_get_related_entity('node', $this->node1->id());
    $this->assertEqual($this->node4->id(), $related->id());

    // Confirm this works once the related entity has been cached.
    $related = relation_get_related_entity('node', $this->node1->id());
    $this->assertEqual($this->node4->id(), $related->id());

    // Directional.
    // From Parent to Grandparent.
    $directional_relation_type = RelationType::load($this->relation_types['directional']['id']);
    $related = relation_get_related_entity('node', $this->node3->id(), $directional_relation_type->id(), 1);
    $this->assertEqual($this->node1->id(), $related->id());
    // From Parent to Child.
    $related = relation_get_related_entity('node', $this->node3->id(), $directional_relation_type->id(), 0);
    $this->assertEqual($this->node4->id(), $related->id());

    // Test labels.
    $this->assertEqual($directional_relation_type->label(), 'directional');
    $this->assertEqual($directional_relation_type->reverseLabel(), 'reverse_directional');
    $test_relation_type = RelationType::create(['id' => 'test_relation_type']);
    $test_relation_type->save();
    $this->assertEqual($test_relation_type->label(), 'test_relation_type');
    $this->assertEqual($test_relation_type->reverseLabel(), 'test_relation_type');

    // Delete all relations related to node 4, then confirm that these can
    // no longer be found as related entities.
    $relation_ids = relation_query('node', $this->node4->id())->execute();
    foreach (Relation::loadMultiple($relation_ids) as $relation) {
      $relation->delete();
    }
    $this->assertFalse(relation_get_related_entity('node', $this->node4->id()), 'The entity was not loaded after the relation was deleted.');

    // Test get available relation types .
    $available_articles = $this->container->get('entity.repository.relation')->getAvailable('node', 'article');
    $article_labels = [];
    foreach ($available_articles as $relation) {
      $article_labels[] = $relation->label();
    }
    // Expect 3 available relation types for node article.
    $this->assertEqual($article_labels, ['directional', 'octopus', 'symmetric']);

    $available_users = $this->container->get('entity.repository.relation')->getAvailable('user', '*');
    $user_labels = [];
    foreach ($available_users as $relation) {
      $user_labels[] = $relation->label();
    }
    // Expect 2 available relation types for user.
    $this->assertEqual($user_labels, ['directional_entitydifferent', 'symmetric']);
  }

  /**
   * Tests saving of relations.
   */
  public function testRelationSave() {
    foreach ($this->relation_types as $value) {
      $relation_type = $value['id'];
      $endpoints = $this->endpoints;
      if (isset($value['min_arity'])) {
        $endpoints = $value['min_arity'] == 1 ? $this->endpoints_unary : $this->endpoints_4;
      }
      if ($relation_type == 'directional_entitydifferent') {
        $endpoints = $this->endpoints_entitydifferent;
      }
      $relation = Relation::create(array('relation_type' => $relation_type));
      $relation->endpoints = $endpoints;
      $relation->save();
      $this->assertTrue($relation->id(), 'Relation created.');
      $count = count($relation->endpoints);
      $this->assertEqual($count, count($endpoints));
      $this->assertEqual($relation->arity->value, count($endpoints));
      $this->assertEqual($relation->bundle(), $relation_type);
      foreach ($relation->endpoints as $endpoint) {
        $need_ids[$endpoint->entity_id] = TRUE;
      }
      foreach ($relation->endpoints as $delta => $endpoint) {
        $this->assertEqual($endpoint->entity_type, $endpoints[$delta]['entity_type'], 'The entity type is ' . $endpoints[$delta]['entity_type'] . ': ' . $endpoint->entity_type);
        $this->assertTrue(isset($need_ids[$endpoint->entity_id]), 'The entity ID is correct: ' . $need_ids[$endpoint->entity_id]);
        unset($need_ids[$endpoint->entity_id]);
      }
      $this->assertFalse($need_ids, 'All ids found.');
      // Confirm the relation_id in revision table.
      $revision = \Drupal::database()->select('relation_revision', 'v')
          ->fields('v', array('relation_id'))
          ->condition('revision_id', $relation->getRevisionId())
          ->execute()
          ->fetchAllAssoc('relation_id');
      $this->assertTrue(array_key_exists($relation->id(), $revision), 'Relation revision created.');
    }
  }

  /**
   * Tests relation delete.
   */
  public function testRelationDelete() {
    // Invalid relations are deleted when any endpoint entity is deleted.
    // Octopus relation is valid with 3 endpoints, currently it has 4.
    $this->node1->delete();
    $this->assertTrue(Relation::load($this->relation_id_octopus), 'Relation is not deleted.');
    $this->node2->delete();
    $this->assertFalse(Relation::load($this->relation_id_octopus), 'Relation is deleted.');
  }

  /**
   * Tests relation revisions.
   */
  public function testRelationRevision() {
    /* todo Uncomment when revisions are fixed.
    $first_user = $this->drupalCreateUser(['edit relations']);
    $second_user = $this->drupalCreateUser(['edit relations']);

    $this->drupalLogin($first_user);
    $relation = Relation::create(array('relation_type' => $this->relation_type_octopus));
    $relation->endpoints = $this->endpoints_4;
    $relation->save();
    $relation_id = $relation->id();
    $this->assertEqual($relation->id(), $first_user->id(), 'Relation uid set to logged in user.');
    $revision_id = $relation->getRevisionId();

    // Relation should still be owned by the first user.
    $this->drupalLogin($second_user);
    $relation = Relation::load($relation_id);
    $relation = $this->container->get('entity_type.manager')->getStorage('relation')->load($relation_id);
    $relation->save();
    $this->assertEqual($relation->id(), $first_user->id(), 'Relation uid did not get changed to a user different to original.');

    // Relation revision authors should not be identical though.
    $first_revision = $this->container->get('entity_type.manager')->getStorage('relation')->loadRevision($revision_id);
    $second_revision = $this->container->get('entity_type.manager')->getStorage('relation')->loadRevision($relation->getRevisionId());
    $this->assertNotIdentical($first_revision->revision_uid, $second_revision->revision_uid, 'Each revision has a distinct user.');
    */
  }

}
