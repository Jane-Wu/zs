<?php

/**
 * @file
 * Contains \Drupal\relation\RelationAccessControlHandler.
 */

namespace Drupal\relation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for Relations.
 */
class RelationAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($parent = parent::checkAccess($entity, $operation, $account)) {
      return $parent;
    }

    if ($operation === 'create' && $account->hasPermission('create relations')) {
      return TRUE;
    }
    elseif ($operation === 'view' && $account->hasPermission('access relations')) {
      return TRUE;
    }
    elseif ($operation === 'update' && $account->hasPermission('edit relations')) {
      return TRUE;
    }
    elseif ($operation === 'delete' && $account->hasPermission('delete relations')) {
      return TRUE;
    }
  }

}
