<?php

/**
 * @file
 * Contains \Drupal\relation\RelationListBuilder.
 */

namespace Drupal\relation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of relation types.
 *
 * @todo: add filters
 */
class RelationListBuilder extends EntityListBuilder {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Title');
    $header['relation_type'] = t('Type');
    $header['endpoints'] = t('Endpoints');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->label(),
    ) + $entity->toUrl()->toRenderArray();

    $storage_handler = \Drupal::entityTypeManager()->getStorage($entity->getEntityType()->getBundleEntityType());
    $bundle = $storage_handler->load($entity->bundle());
    $row['relation_type']['data'] = array(
      '#type' => 'link',
      '#title' => $bundle->label(),
    ) + $bundle->toUrl()->toRenderArray();

    // Sort entities by their type.
    foreach ($entity->endpoints as $endpoint) {
      $entities[$endpoint->entity_type][] = $endpoint->entity_id;
    }

    $relation_entities = array();
    $entity_count_total = 0;
    $entity_count = 0;
    foreach ($entities as $type => $ids) {
      $entity_count_total += count(array_unique($ids));
      $storage_handler = \Drupal::entityTypeManager()->getStorage($type);
      $endpoint_entities = $storage_handler->loadMultiple($ids);
      foreach ($endpoint_entities as $endpoint_entity) {
        $entity_count++;
        $relation_entities[] = array(
          '#type' => 'link',
          '#title' => $endpoint_entity->label(),
        ) + $endpoint_entity->toUrl()->toRenderArray();
      }
    }

    if ($entity_count_total != $entity_count) {
      $relation_entities[] = \Drupal::translation()->formatPlural(
        $entity_count_total - $entity_count,
        'Missing @count entity',
        'Missing @count entities'
      );
    }

    $row['endpoints']['data']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $relation_entities,
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = t('No relations exist.');
    return $build;
  }

}
