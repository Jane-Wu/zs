<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Plugin\Field\FieldType\RelationEndpointItem.
 */

namespace Drupal\relation_endpoint\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'relation_endpoint' field type.
 *
 * @FieldType(
 *   id = "relation_endpoint",
 *   label = @Translation("Relation endpoint"),
 *   description = @Translation("This field contains the endpoints of the relation"),
 *   instance_settings = {
 *   },
 *   default_widget = "relation_endpoint",
 *   default_formatter = "relation_endpoint",
 *   list_class = "\Drupal\relation_endpoint\Type\RelationField",
 *   no_ui = TRUE
 * )
 */
class RelationEndpointItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_type'] = DataDefinition::create('string')
      ->setLabel(t('Entity_type of this relation end-point.'));

    $properties['entity_id'] = DataDefinition::create('integer')
      ->setLabel(t('Entity_id of this relation end-point.'));

    $properties['r_index'] = DataDefinition::create('integer')
      ->setLabel(t('The index of this row in this relation.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'entity_type' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Entity_type of this relation end-point.',
        ),
        'entity_id' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Entity_id of this relation end-point.',
        ),
        'r_index' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The index of this row in this relation. The highest index in the relation is stored as "arity" in the relation table.',
        ),
      ),
      'indexes' => array(
        'relation' => array('entity_type', 'entity_id', 'r_index'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('entity_id')->getValue();
    return $value === NULL || $value === '';
  }

}
