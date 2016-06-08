<?php

/**
 * @file
 * Views relationship support.
 */

namespace Drupal\relation\Plugin\views\relationship;

use Drupal\views\Views;
use Drupal\views\Plugin\views\relationship\Standard as RelationshipStandard;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Relate entities using a Relation endpoint.
 *
 * @ViewsRelationship("relation_relationship")
 */
class RelationRelationship extends RelationshipStandard {

  /**
   * Define r_index option.
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['r_index'] = array('default' => -1);
    $options['entity_deduplication_left'] = array('default' => FALSE);
    $options['entity_deduplication_right'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Let the user choose r_index.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Check if this relation is entity-to-entity or entity-to-relation /
    // relation-to-entity.
    $endpoints_twice = isset($this->definition['entity_type_left']) && isset($this->definition['entity_type_right']);

    if ($this->definition['directional']) {
      $form['r_index'] = array(
        '#type' => 'select',
        '#options' => array(
          -1 => t('Any'),
          0 => t('Source'),
          1 => t('Target'),
        ),
        '#title' => t('Position of the relationship base'),
        '#default_value' => $this->options['r_index'],
        // check_plain()'d in the definition.
        '#description' => t('Select whether the entity you are adding the relationship to is source or target of @relation_type_label relation.', array('@relation_type_label' => $this->definition['label'])),
      );
    }
    foreach (array('left', 'right') as $key) {
      if (isset($this->definition['entity_type_' . $key])) {
        $form['entity_deduplication_' . $key] = array(
          '#type' => 'checkbox',
          '#title' => $endpoints_twice ?
          t('Avoid @direction @type duplication', array('@direction' => t($key), '@type' => $this->definition['entity_type_' . $key])) :
          t('Avoid @type duplication', array('@type' => $this->definition['entity_type_' . $key])),
          '#default_value' => $this->options['entity_deduplication_' . $key],
          '#description' => t('When creating a chain of Views relationships for example from node to relation and then from relation to node (both via the same relation type) then each node will display on both ends. Check this option to avoid this kind of duplication.'),
        );
      }
    }
  }

  /**
   *
   */
  public function query() {
    $table_mapping = \Drupal::entityTypeManager()->getStorage('relation')->getTableMapping();
    $endpoints_field = FieldStorageConfig::loadByName('relation', 'endpoints');

    $relation_data_table_name = $table_mapping->getDedicatedDataTableName($endpoints_field);
    $entity_id_field_name = $table_mapping->getFieldColumnName($endpoints_field, 'entity_id');
    $entity_type_field_name = $table_mapping->getFieldColumnName($endpoints_field, 'entity_type');
    $r_index_field_name = $table_mapping->getFieldColumnName($endpoints_field, 'r_index');

    $join_type = empty($this->options['required']) ? 'LEFT' : 'INNER';
    $endpoints_twice = isset($this->definition['entity_type_left']) && isset($this->definition['entity_type_right']);

    $this->ensureMyTable();

    // Join the left table with the entity type to the endpoints field data
    // table.
    $configuration = array(
      'left_table' => $this->tableAlias,
      'left_field' => $this->realField,
      'table'      => $relation_data_table_name,
      'type'       => $join_type,
      'extra'      => array(
        array(
          'field' => 'bundle',
          'value' => $this->definition['relation_type'],
        ),
      ),
    );

    if (isset($this->definition['entity_type_left'])) {
      // The left table is an entity, not a relation.
      $configuration['field'] = $entity_id_field_name;
      $this->ensureNoDuplicateEntities($configuration['extra'], $this->options['entity_deduplication_left'], $this->definition['relation_type'], $this->definition['entity_type_left'], $this->tableAlias, $this->realField);
      $configuration['extra'][] = array(
        'field' => $entity_type_field_name,
        'value' => $this->definition['entity_type_left'],
      );
    }
    else {
      // The left table is relation.
      $configuration['field'] = 'entity_id';
    }

    if ($this->definition['directional'] && $this->options['r_index'] > -1) {
      $configuration['extra'][] = array(
        'field' => $r_index_field_name,
        'value' => $this->options['r_index'],
      );
    }

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);

    $join->adjusted = TRUE;
    $l = $this->query->addTable($relation_data_table_name, $this->relationship, $join);

    if ($endpoints_twice) {
      // Execute a self-join.
      $configuration = array(
        'left_table' => $l,
        'left_field' => 'entity_id',
        'table' => $relation_data_table_name,
        'field' => 'entity_id',
        'type' => $join_type,
        'extra' => array(
          array(
            'field' => $entity_type_field_name,
            'value' => $this->definition['entity_type_right'],
          ),
        ),
      );

      if ($this->definition['entity_type_left'] == $this->definition['entity_type_right']) {
        $configuration['extra'][] = array(
          // This definition is a bit funny but there's no other way to tell
          // Views to use an expression in join extra as it is.
          'field' => $r_index_field_name . ' !=  ' . $l . '.' . $r_index_field_name . ' AND 1',
          'value' => 1,
        );
      }

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $join->adjusted = TRUE;
      $r = $this->query->addTable($relation_data_table_name, $this->relationship, $join);
    }
    else {
      $r = $l;
    }

    $configuration = array(
      'left_table' => $r,
      'table'      => $this->definition['base'],
      'field'      => $this->definition['base field'],
      'type'       => $join_type,
    );
    if (isset($this->definition['entity_type_right'])) {
      // We are finishing on an entity table.
      $configuration['left_field'] = $entity_id_field_name;
      $this->ensureNoDuplicateEntities($configuration['extra'], $this->options['entity_deduplication_right'], $this->definition['relation_type'], $this->definition['entity_type_right'], $r, $entity_id_field_name);
      $configuration['extra'][] = array(
        'table' => $r,
        'field' => $entity_type_field_name,
        'value' => $this->definition['entity_type_right'],
      );
    }
    else {
      // We are finishing on relation.
      $configuration['left_field'] = 'entity_id';
    }

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $join->adjusted = TRUE;
    // Use a short alias for this:
    $alias = $this->definition['base'] . '_' . $this->table;
    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);
  }

  /**
   *
   */
  protected function ensureNoDuplicateEntities(&$extra, $check, $relation_type, $entity_type, $table, $field) {
    if ($check && isset($this->view->relation_entity_tables[$entity_type][$relation_type])) {
      foreach ($this->view->relation_entity_tables[$entity_type][$relation_type] as $expression) {
        $extra[] = array(
          'table' => NULL,
          'field' => "$expression != $table.$field AND 1",
          'value' => 1,
        );
      }
    }
    $this->view->relation_entity_tables[$entity_type][$relation_type][] = "$table.$field";
  }

}
