<?php

/**
 * @file
 * Contains \Drupal\relation_devel\Form\RelationGenerate.
 */

namespace Drupal\relation_devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\relation\Entity\RelationType;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for generating dummy relations.
 */
class RelationGenerate extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'relation_devel_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $relation_types = RelationType::loadMultiple();

    if (empty($relation_types)) {
      $form['explanation']['#markup'] = t("You must create a relation type before you can generate relations.");
      return $form;
    }
    foreach ($relation_types as $relation_type) {
      $options[$relation_type->id()] = array(
        'label' => $relation_type->label(),
      );
    }

    $header = array(
      'label' => t('Relation type'),
    );

    $form['relation_types'] = array(
      '#type' => 'tableselect',
      '#title' => t('Relation types'),
      '#description' => t('Select relation types to create relations from. If no types are selected, relations will be generated for all types.'),
      '#options' => $options,
      '#header' => $header,
    );
    $form['relation_kill'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete all relations in these relation types before generating new relations'),
    );

    $form['relation_number'] = array(
      '#type' => 'number',
      '#title' => t('How many relations would you like to generate of each type?'),
      '#default_value' => 10,
      '#size' => 10,
      '#min' => 0,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $number = $form_state['values']['relation_number'];
    $relation_types = $form_state['values']['relation_types'];
    $kill = $form_state['values']['relation_kill'];
    include_once drupal_get_path('module', 'relation') . '/relation.drush.inc';
    $relation_types = array_keys(array_filter($relation_types));
    $relation_types = empty($relation_types) ? NULL : $relation_types;
    $relation_ids = relation_generate_relations($number, $relation_types, $kill);
  }

}
