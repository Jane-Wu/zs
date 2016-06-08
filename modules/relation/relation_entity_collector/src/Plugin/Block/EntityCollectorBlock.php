<?php

/**
 * @file
 * Contains \Drupal\relation_entity_collector\Plugin\Block\EntityCollectorBlock.
 */

namespace Drupal\relation_entity_collector\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Relation entity collector' block.
 *
 * @Block(
 *   id = "relation_entity_collector",
 *   admin_label = @Translation("Relation Entity Collector"),
 *   category = @Translation("Relation")
 * )
 */
class EntityCollectorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $account->hasPermission('administer relations') || $account->hasPermission('create relations');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\relation_entity_collector\Form\EntityCollector');
  }

}
