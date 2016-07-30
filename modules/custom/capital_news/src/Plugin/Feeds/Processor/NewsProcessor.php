<?php

namespace Drupal\capital_news\Plugin\Feeds;

/**
 * Defines a node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:node1",
 *   title = @Translation("News"),
 *   description = @Translation("News."),
 *   entity_type = "node",
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class NodeProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  protected function entityLabel() {
    return $this->t('News');
  }

  /**
   * {@inheritdoc}
   */
  protected function entityLabelPlural() {
    return $this->t('News');
  }

}
