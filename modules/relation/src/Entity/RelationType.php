<?php

namespace Drupal\relation\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\relation\RelationTypeInterface;

/**
 * Defines relation type entity.
 *
 * @ConfigEntityType(
 *   id = "relation_type",
 *   label = @Translation("Relation type"),
 *   module = "relation",
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "render" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\relation\RelationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\relation\RelationTypeForm",
 *       "edit" = "Drupal\relation\RelationTypeForm",
 *       "delete" = "Drupal\relation\Form\RelationTypeDeleteConfirm"
 *     },
 *   },
 *   admin_permission = "administer relation types",
 *   config_prefix = "type",
 *   bundle_of = "relation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "reverse_label",
 *     "directional",
 *     "transitive",
 *     "r_unique",
 *     "min_arity",
 *     "max_arity",
 *     "source_bundles",
 *     "target_bundles",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/relation/manage/{relation_type}",
 *     "delete-form" = "/admin/structure/relation/manage/{relation_type}/delete",
 *     "collection" = "/admin/structure/relation",
 *   }
 * )
 */
class RelationType extends ConfigEntityBundleBase implements RelationTypeInterface {

  /**
   * The machine name of this relation type.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of this type.
   *
   * Defaults to relation type id.
   *
   * @var string
   */
  public $label;

  /**
   * The reverse human-readable name of this type.
   *
   * Only used for directional relations.
   *
   * @var string
   */
  public $reverse_label;

  /**
   * Whether this relation type is directional. If not, all indexes are ignored.
   *
   * @var bool
   */
  public $directional  = FALSE;

  /**
   * Whether this relation type is transitive.
   *
   * @var bool
   */
  public $transitive  = FALSE;

  /**
   * Whether relations of this type are unique.
   *
   * @var bool
   */
  public $r_unique  = FALSE;

  /**
   * The minimum number of entities that can make up a relation of this type.
   *
   * @var int
   */
  public $min_arity  = 2;

  /**
   * The maximum number of entities that can make up a relation of this type.
   *
   * Similar to field cardinality.
   *
   * @var int
   */
  public $max_arity  = 2;

  /**
   * List of entity bundles that can be used as relation sources.
   *
   * This is used for both directional and non-directional relations. Bundle key
   * arrays are of the form 'entity:bundle', eg. 'node:article', or 'entity:*'
   * for all bundles of the type.
   *
   * @var array
   */
  public $source_bundles = array();

  /**
   * List of entity bundles that can be used as relation targets.
   *
   * This is the same format as source bundles, but is only used for directional
   * relations.
   *
   * @var array
   */
  public $target_bundles = array();

  /**
   * {@inheritdoc}
   */
  public function reverseLabel() {
    return $this->reverse_label;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure endpoints field is attached to relation type.
    if (!$update) {
      relation_add_endpoint_field($this);
    }
    else {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles($direction = NULL) {
    $pairs = array();

    if ((!$direction || $direction == 'source') && is_array($this->source_bundles)) {
      $pairs += $this->source_bundles;
    }

    if ((!$direction || $direction == 'target') && is_array($this->target_bundles)) {
      $pairs += $this->target_bundles;
    }

    $bundles = array();
    foreach ($pairs as $pair) {
      list($entity_type_id, $bundle) = explode(':', $pair, 2);
      $bundles[$entity_type_id][$bundle] = $bundle;
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (empty($this->label)) {
      $this->label = $this->id();
    }

    // Directional relations should have a reverse label. If they are symmetric
    // or if they don't have it, fill it with the label.
    if (empty($this->reverse_label)) {
      $this->reverse_label = $this->label;
    }
  }

}
