<?php

namespace Drupal\relation_entity_collector\Tests;

use Drupal\relation\Tests\RelationTestBase;

/**
 * Tests the Relation Entity Collector.
 *
 * Functional test of Relation's integration with the Entity Collector.
 *
 * @group Relation
 */
class RelationEntityCollectorTest extends RelationTestBase {
  public static $modules = ['relation_entity_collector'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
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
   * Add relations to Node 1 and to Node 3 and then check that they are related.
   */
  function testEntityCollector() {
    /* todo Uncomment when EntityCollectionBlock is fixed.
    $node1key = 'node:' . $this->node1->id();
    $node3key = 'node:' . $this->node3->id();

    $relation_type = $this->relation_types['symmetric']['id'];
    $edit = array(
      "relation_type" => $relation_type,
      "entity_key" => $node1key,
    );
    $this->drupalPostForm('node', $edit, t('Pick'));
    $edit = array(
      "relation_type" => $relation_type,
      "entity_key" => $node3key,
    );
    $this->drupalPostForm('node', $edit, t('Pick'));
    $this->drupalPostForm('node', array(), t('Save relation'));
    // Now figure out the new relation id.
    $result = array_keys(relation_query('node', $this->node3->nid)
      ->condition('relation_type', $relation_type)
      ->execute());
    $path = 'relation/' . $result[0];
    $link = l($relation_type, $path);
    // Rebuild the message using the known bundle and entity labels to make sure
    // the message contains those.
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
    $node1_label = $bundles['article']['label'] . ': ' . $this->node1->label();
    $node3_label = $bundles['page']['label'] . ': ' . $this->node3->label();
    $items = array(
      $node1_label,
      $node3_label,
    );
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $list = \Drupal::service('renderer')->render($item_list);
    $message = t('Created new !link from !list', array('!link' => $link, '!list' => $list));
    $this->assertRaw($message, 'Created a new relation.');
    $this->drupalGet($path);
    $node1_uri = $this->node1->uri();
    $node3_uri = $this->node3->uri();
    $this->assertRaw(l($this->node1->label(), $node1_uri['path'], $node1_uri['options']), 'Node1 link found');
    $this->assertRaw(l($this->node3->label(), $node3_uri['path'], $node3_uri['options']), 'Node1 link found');
    */
  }

}
