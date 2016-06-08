<?php

/**
 * @file
 * Support for relation destinations.
 */
use Drupal\relation\Entity\Relation;

/**
 * Destination class implementing migration into relation.
 */
class MigrateDestinationRelation extends MigrateDestinationEntity {
  /**
   *
   */
  static public function getKeySchema() {
    return array(
      'relation_id' => array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'Unique relation id (entity id).',
      ),
    );
  }

  /**
   * Basic initialization.
   *
   * @param string $bundle
   *   A.k.a. the content type (page, article, etc.) of the node.
   * @param array $options
   *   Options applied to nodes.
   */
  public function __construct($bundle, array $options = array()) {
    parent::__construct('relation', $bundle, $options);
  }

  /**
   * Returns a list of fields available to be mapped for the relation type (bundle).
   *
   * @return array
   *  Keys: machine names of the fields (to be passed to addFieldMapping)
   *  Values: Human-friendly descriptions of the fields.
   */
  public function fields() {
    $fields = array();
    // First the core (relation table) properties.
    $fields['relation_id'] = t('Relation: Existing relation ID');
    $fields['is_new'] = t('Relation: Indicates a new relation with the specified relation ID should be created');
    $fields['uid'] = t('Relation: Authored by (uid)');
    $fields['created'] = t('Relation: Created timestamp');
    $fields['changed'] = t('Relation: Modified timestamp');

    // Then add in anything provided by handlers.
    $fields += migrate_handler_invoke_all('Entity', 'fields', $this->entityType, $this->bundle);
    $fields += migrate_handler_invoke_all('Relation', 'fields', $this->entityType, $this->bundle);

    return $fields;
  }

  /**
   * Delete a batch of relations at once.
   *
   * @param $relation_ids
   *   Array of relation IDs to be deleted.
   */
  public function bulkRollback(array $relation_ids) {
    migrate_instrument_start('relation_delete_multiple');
    $this->prepareRollback($relation_ids);
    relation_delete_multiple($relation_ids);
    $this->completeRollback($relation_ids);
    migrate_instrument_stop('relation_delete_multiple');
  }

  /**
   * Import a single relation.
   *
   * @param $relation
   *   Relation object to build. Prefilled with any fields mapped in the Migration.
   * @param $row
   *   Raw source data object - passed through to prepare/complete handlers.
   *
   * @return array
   *  Array of key fields (relation ID only in this case) of the relation that was saved if
   *  successful. FALSE on failure.
   */
  public function import(stdClass $relation, stdClass $row) {
    // Updating previously-migrated content?
    $migration = Migration::currentMigration();
    if (isset($row->migrate_map_destid1)) {
      // Make sure is_new is off.
      $relation->is_new = FALSE;
      $relation_id = $relation->id();
      if (isset($relation_id)) {
        if ($relation_id != $row->migrate_map_destid1) {
          throw new MigrateException(t("Incoming relation_id !relation_id and map destination relation_id !destid1 don't match",
              array('!relation_id' => $relation->id(), '!destid1' => $row->migrate_map_destid1)
            ));
        }
      }
      else {
        $relation->set('id', $row->migrate_map_destid1);
      }
      // Get the existing revision_id, so updates don't generate notices.
      $values = \Drupal::database()->select('relation', 'r')->fields('r', array('revision_id'))->condition('relation_id', $relation->id())->execute()->fetchAssoc();
      $relation->set('revision_id', $values['revision_id']);
    }
    if ($migration->getSystemOfRecord() == Migration::DESTINATION) {
      $relation_id = $relation->id();
      if (!isset($relation_id)) {
        throw new MigrateException(t('System-of-record is DESTINATION, but no destination relation_id provided'));
      }

      $old_relation = Relation::load($relation->id());

      if (!isset($relation->created)) {
        $relation->created = $old_relation->created;
      }
      $relation_revision_id = $relation->getRevisionId();
      if (!isset($relation_revision_id)) {
        $relation->set('revision_id', $old_relation->getRevisionId());
      }
      if (!isset($relation->uid)) {
        $relation->uid = $old_relation->uid;
      }
    }

    // Set some required properties.
    if (!isset($relation->uid)) {
      $relation->uid = $GLOBALS['user']->uid;
    }
    // Set type before invoking prepare handlers - they may take type-dependent actions.
    $relation->relation_type = $this->bundle;

    if ($migration->getSystemOfRecord() == Migration::SOURCE) {

      // relation_save() will blow these away, so save them here and
      // save them later.
      if (isset($relation->created)) {
        $created = MigrationBase::timestamp($relation->created);
      }
      if (isset($relation->changed)) {
        $changed = MigrationBase::timestamp($relation->changed);
      }
    }

    // Trying to update an existing relation.
    if ($migration->getSystemOfRecord() == Migration::DESTINATION) {
      // Incoming data overrides existing data, so only copy non-existent fields.
      foreach ($old_node as $field => $value) {
        // An explicit NULL in the source data means to wipe to old value (i.e.,
        // don't copy it over from $old_node)
        if (property_exists($relation, $field) && $relation->$field === NULL) {
          // Ignore this field.
        }
        elseif (!isset($relation->$field)) {
          $relation->$field = $old_relation->$field;
        }
      }
    }

    // Invoke migration prepare handlers.
    $this->prepare($relation, $row);

    $relation_id = $relation->id();
    if (isset($relation_id) && empty($relation->is_new)) {
      $updating = TRUE;
    }
    else {
      $updating = FALSE;
    }

    // Save relation object.
    migrate_instrument_start('relation_save');
    $relation_id = relation_save($relation);
    migrate_instrument_stop('relation_save');

    if (isset($relation_id)) {
      if ($updating) {
        $this->numUpdated++;
      }
      else {
        $this->numCreated++;
      }

      // Update changed and created dates if needed.
      if (isset($changed)) {
        \Drupal::database()->update('relation')->fields(array('changed' => $changed))->condition('relation_id', $relation->id())->execute();
        $relation->changed = $changed;
      }

      if (isset($created)) {
        \Drupal::database()->update('relation')->fields(array('created' => $created))->condition('relation_id', $relation->id())->execute();
        $relation->created = $created;
      }

      $return = array($relation->id());
    }
    else {
      $return = FALSE;
    }

    $this->complete($relation, $row);
    return $return;
  }

}
