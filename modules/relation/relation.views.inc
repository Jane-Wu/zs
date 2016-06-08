<?php

/**
 * @file
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\relation\Entity\RelationType;

/**
 * @file
 * Views support.
 */

/**
 * Implements hook_views_data().
 */
function relation_views_data() {
  // Define the base group of this table.
  $data['relation']['table']['group']  = t('Relation');

  // Advertise this table as a possible base table.
  $data['relation']['table']['base'] = array(
    'field' => 'relation_id',
    'title' => t('Relation'),
    'weight' => -10,
  );
  $data['relation']['table']['entity type'] = 'relation';
  $data['relation']['relation_id'] = array(
    'title' => t('Relation ID'),
    'help' => t('The relation ID.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );

  $data['relation']['relation_type'] = array(
    'title' => t('Relation type'),
    'help' => t('The relation type.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'in_operator',
      'options callback' => 'relation_get_types_options',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );

  $data['relation']['revision_id'] = array(
    'title' => t('Revision ID'),
    'help' => t('The relation revision ID.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );
  $data['relation']['uid'] = array(
    'title' => t('Uid'),
    'help' => t('The relation uid.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
    'relationship' => array(
      'label' => 'relation author',
      'title' => t('Relation Author'),
      'base' => 'users_field_data',
      'base field' => 'uid',
    ),
  );

  $data['relation']['created'] = array(
    'title' => t('Create Date'),
    'help' => t('The date when the relation was created.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'date',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );

  $data['relation']['changed'] = array(
    'title' => t('Change Date'),
    'help' => t('The date when the relation was last changed.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'date',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );

  $data['relation']['arity'] = array(
    'title' => t('Number of endpoints'),
    'help' => t('The number of endpoints in this relation.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'sort',
    ),
  );

  // View link.
  $data['relation']['link'] = array(
    'title' => t('Link'),
    'help' => t('Provide a simple link to the relation entity.'),
    'field' => array(
      'handler' => 'views_handler_field_relation_link',
      'click sortable' => TRUE,
      'real field' => 'relation_id',
      'additional fields' => array(
        'relation_id',
      ),
    ),
  );

  // Edit link.
  $data['relation']['edit'] = array(
    'title' => t('Edit link'),
    'help' => t('Provide a simple link to edit the relation entity.'),
    'field' => array(
      'handler' => 'views_handler_field_relation_link_edit',
      'click sortable' => TRUE,
      'real field' => 'relation_id',
      'additional fields' => array(
        'relation_id',
      ),
    ),
  );

  // Delete link.
  $data['relation']['delete'] = array(
    'title' => t('Delete link'),
    'help' => t('Provide a simple link to delete the relation entity.'),
    'field' => array(
      'handler' => 'views_handler_field_relation_link_delete',
      'click sortable' => TRUE,
      'real field' => 'relation_id',
      'additional fields' => array(
        'relation_id',
      ),
    ),
  );

  return $data;
}


/**
 * Implements hook_views_data_alter().
 */
function relation_views_data_alter(&$data) {
  $entity_info = Drupal::entityTypeManager()->getDefinitions();

  // Build the relations between the different tables.
  foreach (RelationType::loadMultiple() as $relation_type) {
    $type = $relation_type->id();
    $target_index = $relation_type->directional ? 'target_bundles' : 'source_bundles';
    foreach ($relation_type->source_bundles as $source_bundle) {
      list($source_entity_type) = explode(':', $source_bundle, 2);
      $source_table = $entity_info[$source_entity_type]->getDataTable();

      // Translatable.
      $placeholders = array(
        '@left' => $source_entity_type,
        '@relation_type_label' => $relation_type->label,
        '@relation_type_reverse_label' => $relation_type->reverse_label,
        '@arrow' => $relation_type->directional ? '→' : '↔',
      );

      if ($source_table) {
        $data[$source_table]['relation_base_left_' . $type] = array(
          'title' => t('Relation: @relation_type_label (@left → relation)', $placeholders),
          'help' => t('Provides a relationship from @left to the relation table via the relation @relation_type_label', $placeholders),
          'relationship' => array(
            'label' => $relation_type->label,
            'base' => 'relation',
            'base field' => 'relation_id',
            'relationship field' => $entity_info[$source_entity_type]->getKey('id'),
            'id' => 'relation_relationship',
            'relation_type' => $type,
            'entity_type_left' => $source_entity_type,
            'directional' => $relation_type->directional,
          ),
        );
      }

      foreach ($relation_type->$target_index as $target_bundle) {
        list($target_entity_type) = explode(':', $target_bundle, 2);
        $target_table = $entity_info[$target_entity_type]->getBaseTable();

        $placeholders['@right'] = $target_entity_type;
        // Provide forward relationships.
        if ($source_table && $target_table) {
          $data[$source_table]['relation_' . $type . '_' . $target_entity_type] = array(
            'title' => t('Relation: @relation_type_label (@left @arrow @right)', $placeholders),
            'help' => t('Provides a relationship from @left to @right via the relation @relation_type_label', $placeholders),
            'relationship' => array(
              'label' => $relation_type->label,
              'base' => $target_table,
              'base field' => $entity_info[$target_entity_type]->getKey('id'),
              'relationship field' => $entity_info[$source_entity_type]->getKey('id'),
              'id' => 'relation_relationship',
              'relation_type' => $type,
              'entity_type_left' => $source_entity_type,
              'entity_type_right' => $target_entity_type,
              'directional' => $relation_type->directional,
            ),
          );
        }

        if ($target_table) {
          $data['relation']['relation_base_' . $type . '_' . $target_entity_type] = array(
            'title' => t('Relation: @relation_type_label (relation → @right)', $placeholders),
            'help' => t('Provides a relationship from the relation table to @right via the relation @relation_type_label', $placeholders),
            'relationship' => array(
              'label' => $relation_type->label,
              'base' => $target_table,
              'base field' => $entity_info[$target_entity_type]->getKey('id'),
              'relationship field' => 'relation_id',
              'id' => 'relation_relationship',
              'relation_type' => $type,
              'entity_type_right' => $target_entity_type,
              'directional' => $relation_type->directional,
            ),
          );
        }

        // Provide reverse relationships.
        if ($target_entity_type != $source_entity_type) {
          if ($source_table && $target_table) {
            $data[$target_table]['relation_' . $type . '_' . $source_entity_type] = array(
              'title' => t('Relation: @relation_type_reverse_label (@right @arrow @left)', $placeholders),
              'help' => t('Provides a relationship from @right to @left via the relation @relation_type_reverse_label', $placeholders),
              'relationship' => array(
                'label' => $relation_type->reverse_label,
                'base' => $source_table,
                'base field' => $entity_info[$source_entity_type]->getKey('id'),
                'relationship field' => $entity_info[$target_entity_type]->getKey('id'),
                'id' => 'relation_relationship',
                'relation_type' => $type,
                'entity_type_left' => $target_entity_type,
                'entity_type_right' => $source_entity_type,
                'directional' => $relation_type->directional,
              ),
            );
          }

          if ($target_table) {
            $data[$target_table]['relation_base_right_' . $type] = array(
              'title' => t('Relation: @relation_type_reverse_label (@right → relation)', $placeholders),
              'help' => t('Provides a relationship from @right to the relation table via the relation @relation_type_reverse_label. Usually only needed to access the fields of the relation itself.', $placeholders),
              'relationship' => array(
                'label' => $relation_type->reverse_label,
                'base' => 'relation',
                'base field' => 'relation_id',
                'relationship field' => $entity_info[$target_entity_type]->getKey('id'),
                'id' => 'relation_relationship',
                'relation_type' => $type,
                'entity_type_right' => $target_entity_type,
                'directional' => $relation_type->directional,
              ),
            );
          }

          if ($source_table) {
            $data['relation']['relation_base_' . $type . '_' . $source_entity_type] = array(
              'title' => t('Relation: @relation_type_reverse_label (relation → @left)', $placeholders),
              'help' => t('Provides a relationship from the relation table to @left via the relation @relation_type_reverse_label', $placeholders),
              'relationship' => array(
                'label' => $relation_type->reverse_label,
                'base' => $source_table,
                'base field' => $entity_info[$source_entity_type]->getKey('id'),
                'relationship field' => 'relation_id',
                'id' => 'relation_relationship',
                'relation_type' => $type,
                'entity_type_left' => $source_entity_type,
                'directional' => $relation_type->directional,
              ),
            );
          }
        }
      }
    }
  }
}

/**
 * Implements hook_form_views_ui_add_item_form_alter().
 */
function relation_form_views_ui_add_item_form_alter(&$form, $form_state) {
  if ($form_state['type'] == 'relationship' && $form_state['view']->base_table != 'relation') {
    foreach (RelationType::loadMultiple() as $relation_type) {
      foreach (array('source_bundles', 'target_bundles') as $endpoint_bundles) {
        foreach ($relation_type->$endpoint_bundles as $relation_bundle) {
          if (substr($relation_bundle, 0, 9) == 'relation:') {
            return;
          }
        }
      }
    }
    // Collect the relation types already added as a relationship.
    $preg_pieces = array();
    foreach ($form_state['view']->display_handler->get_handlers('relationship') as $relationship) {
      if (get_class($relationship) == 'relation_handler_relationship') {
        $preg_pieces[] = '^relation\.relation_base_' . $relationship->definition['relation_type'];
      }
    }
    if ($preg_pieces) {
      // Filter out impossible Views relationships.
      $relation_type_preg = '/' . implode('|', array_unique($preg_pieces)) . '/';
      foreach (Element::children($form['options']['name']) as $name) {
        if (substr($name, 0, 9) == 'relation.' && !preg_match($relation_type_preg, $name)) {
          $form['options']['name'][$name]['#access'] = FALSE;
        }
      }
    }
  }
}
