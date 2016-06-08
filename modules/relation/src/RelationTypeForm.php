<?php

/**
 * @file
 * Definition of Drupal\relation\RelationTypeForm.
 */

namespace Drupal\relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\relation\Entity\Relation;

/**
 * Form controller for relation edit form.
 */
class RelationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\relation\Entity\RelationType $relation_type */
    $relation_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add relation type');
    }
    elseif ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label relation type', array('%label' => $relation_type->label()));
    }

    $form['#attached'] = array(
      'library' => array('relation/drupal.relation'),
    );
    $form['labels'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('relation-type-form-table'),
      ),
      '#suffix' => '<div class="clearfix"></div>',
    );
    $form['labels']['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('Display name of the relation type. This is also used as the predicate in natural language formatters (ie. if A is related to B, you get "A [label] B")'),
      '#default_value' => $relation_type->label(),
      '#size' => 40,
      '#required'  => TRUE,
    );
    $form['labels']['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $relation_type->id(),
      '#maxlength' => 32,
      '#disabled' => !$relation_type->isNew(),
      '#machine_name' => array(
        'source' => array('labels', 'label'),
        'exists' => '\Drupal\relation\Entity\RelationType::load',
      ),
    );
    $form['labels']['reverse_label'] = array(
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => t('Reverse label'),
      '#description'   => t('Reverse label of the relation type. This is used as the predicate by formatters of directional relations, when you need to display the reverse direction (ie. from the target entity to the source entity). If this is not supplied, the forward label is used.'),
      '#default_value' => $relation_type->reverseLabel(),
      '#states' => array(
        'visible' => array(
          ':input[name="directional"]' => array('checked' => TRUE),
          ':input[name="advanced[max_arity]"]' => array('!value' => '1'),
        ),
        'required' => array(
          ':input[name="directional"]' => array('checked' => TRUE),
          ':input[name="advanced[max_arity]"]' => array('!value' => '1'),
        ),
      ),
    );
    $form['directional'] = array(
      '#type'           => 'checkbox',
      '#title'          => 'Directional',
      '#description'   => t('A directional relation is one that does not imply the same relation in the reverse direction. For example, a "likes" relation is directional (A likes B does not neccesarily mean B likes A), whereas a "similar to" relation is non-directional (A similar to B implies B similar to A. Non-directional relations are also known as symmetric relations.'),
      '#default_value'  => $relation_type->directional,
      '#states' => array(
        'invisible' => array(
          ':input[name="advanced[max_arity]"]' => array('value' => '1'),
        ),
      ),
    );
    // More advanced options, hide by default.
    $form['advanced'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => 50,
    );
    $form['advanced']['transitive'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Transitive'),
      '#description'   => t('A transitive relation implies that the relation passes through intermediate entities (ie. A=>B and B=>C implies that A=>C). For example "Ancestor" is transitive: your ancestor\'s ancestor is also your ancestor. But a "Parent" relation is non-transitive: your parent\'s parent is not your parent, but your grand-parent.'),
      '#default_value'  => $relation_type->transitive,
      '#states' => array(
        'invisible' => array(
          ':input[name="advanced[max_arity]"]' => array('value' => '1'),
        ),
      ),
    );
    $form['advanced']['r_unique'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Unique'),
      '#description'    => t('Whether relations of this type are unique (ie. they can not contain exactly the same end points as other relations of this type).'),
      '#default_value'  => $relation_type->r_unique,
    );
    // These should probably be changed to numerical (validated) textfields.
    $options = array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8');
    $form['advanced']['min_arity'] = array(
      '#type' => 'select',
      '#title' => t('Minimum Arity'),
      '#options' => $options,
      '#description' => t('Minimum number of entities joined by relations of this type (e.g. three siblings in one relation). <em>In nearly all cases you will want to leave this set to 2</em>.'),
      '#default_value' => $relation_type->min_arity ? $relation_type->min_arity : 2,
    );

    $options = array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '0' => t('Infinite'));
    $form['advanced']['max_arity'] = array(
      '#type' => 'select',
      '#title' => t('Maximum Arity'),
      '#options' => $options,
      '#description' => t('Maximum number of entities joined by relations of this type. <em>In nearly all cases you will want to leave this set to 2</em>.'),
      '#default_value' => isset($relation_type->max_arity) ? $relation_type->max_arity : 2,
    );

    $options_bundles = array();
    $entity_info = \Drupal::entityTypeManager()->getDefinitions();
    foreach (\Drupal::service('entity_type.bundle.info')->getAllBundleInfo() as $entity_type => $bundles) {
      $entity_label = $entity_info[$entity_type]->getLabel();
      $entity_label_string = (string) $entity_label;
      $options_bundles[$entity_label_string]["$entity_type:*"] = 'all ' . $entity_label_string . ' bundles';
      foreach ($bundles as $bundle_id => $bundle) {
        $options_bundles[$entity_label_string]["$entity_type:$bundle_id"] = $bundle['label'];
      }
    }

    $form['endpoints'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('relation-type-form-table'),
      ),
      '#suffix' => '<div class="clearfix"></div>',
    );
    $form['endpoints']['source_bundles'] = array(
      '#type' => 'select',
      '#title' => t('Source bundles'),
      '#options' => $options_bundles,
      '#size' => count($options_bundles, COUNT_RECURSIVE),
      '#default_value' => $relation_type->source_bundles,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => t('Select which bundles may be endpoints on relations of this type. Selecting "all <em>entity</em> bundles" includes bundles created in the future.'),
    );
    $form['endpoints']['target_bundles'] = array(
      '#type' => 'select',
      '#title' => t('Target bundles'),
      '#options' => $options_bundles,
      '#size' => count($options_bundles, COUNT_RECURSIVE),
      '#default_value' => $relation_type->target_bundles,
      '#multiple' => TRUE,
      '#description' => t('Select which bundles may be endpoints on relations of this type. Selecting "all <em>entity</em> bundles" includes bundles created in the future.'),
      '#states' => array(
        'visible' => array(
          ':input[name="directional"]' => array('checked' => TRUE),
          ':input[name="advanced[max_arity]"]' => array('!value' => '1'),
        ),
        'required' => array(
          ':input[name="directional"]' => array('checked' => TRUE),
          ':input[name="advanced[max_arity]"]' => array('!value' => '1'),
        ),
      ),
    );

    return parent::form($form, $form_state, $relation_type);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $min_arity = $form_state->getValue('min_arity');
    $max_arity = $form_state->getValue('max_arity');

    // Empty max arity indicates infinite arity.
    if ($max_arity && $min_arity > $max_arity) {
      $form_state->setErrorByName('min_arity', t('Minimum arity cannot be more than maximum arity.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $relation_type = $this->entity;

    $save_message = $relation_type->isNew() ?
      t('The %relation_type relation type has been created.', array('%relation_type' => $relation_type->id())) :
      t('The %relation_type relation type has been saved.', array('%relation_type' => $relation_type->id()));
    if ($relation_type->save()) {
      drupal_set_message($save_message);
      $form_state->setRedirectUrl($relation_type->urlInfo());
    }
    else {
      drupal_set_message(t('Error saving relation type.', 'error'));
    }
  }

}
