<?php

/**
 * @file
 * Contains \Drupal\relation_entity_collector\Form\EntityCollector.
 */

namespace Drupal\relation_entity_collector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\relation\Entity\RelationType;

/**
 * Provides a entity collector form.
 */
class EntityCollector extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relation_entity_collector_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    $form['#attached'] = array(
      'library' => array('relation_entity_controller/drupal.relation_entity_controller'),
    );
    $relation_types_options = relation_get_relation_types_options();
    if (empty($relation_types_options)) {
      $form['explanation']['#markup'] = $this->t('Before you can create relations, you need to create one or more <a href="@url">relation types</a>. Once you\'ve done that, visit any page that loads one or more entities, and use this block to add entities to a new relation. Picked entities stay in the entity_collector until cleared or a relation is created so it is possible to collect the entities from several pages.', array(
        '@url' => $this->url('entity.relation_type.collection'),
      ));
      return $form;
    }

    $relation_type = isset($_SESSION['relation_type']) ? $_SESSION['relation_type'] : '';
    // Forget the selected relation type if it's no longer available.
    if (!isset($relation_types_options[$relation_type])) {
      unset($_SESSION['relation_type']);
      $relation_type = '';
    }
    if ($relation_entities = drupal_static('relation_entities', array())) {
      $options = array();
      foreach ($relation_entities as $entity_type => $entities) {
        foreach ($entities as $entity_id => $entity) {
          $entity_bundle = $entity->bundle();
          if ($relation_type) {
            $relation_type_object = RelationType::load($relation_type);
            $valid = FALSE;
            foreach (array('source_bundles', 'target_bundles') as $property) {
              foreach ($relation_type_object->$property as $allowed_bundle) {
                if ($allowed_bundle == "$entity_type:$entity_bundle" || $allowed_bundle == "$entity_type:*") {
                  $valid = TRUE;
                  break;
                }
              }
            }
          }
          else {
            $valid = TRUE;
          }
          if ($valid) {
            $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
            $options["$entity_type:$entity_id"] = $bundles[$entity_bundle]['label'] . ': ' . $entity->label();
          }
        }
      }
      asort($options);
      $storage['relation_entities_options'] = $options;
      $form_state->setStorage($storage);
    }
    if (empty($storage['relation_entities_options'])) {
      $form['explanation']['#markup'] = t('This block shows all loaded entities on a page and allows adding them to a relation. Please navigate to a page where entities are loaded. Entities picked stay in the entity_collector until cleared or a relation is created so it is possible to collect the entities from several pages.');
      return $form;
    }
    $form['relation_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Relation type'),
      '#default_value' => $relation_type,
      '#options'       => $relation_types_options,
      '#empty_value'   => '',
      '#empty_option'  => $this->t('Select a relation type'),
      '#access'        => empty($_SESSION['relation_edit']),
    );
    $form['entity_key'] = array(
      '#type'           => 'select',
      '#title'          => $this->t('Select an entity'),
      '#options'        => $storage['relation_entities_options'],
      '#default_value'  => '',
      '#description'     => $this->t('Selector shows all !entities loaded on this page.', array(
        '!entities' => $this->l($this->t('entities'), Url::fromUri('http://drupal.org/glossary#entity')),
      )),
    );
    $form['pick'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Pick'),
      '#submit' => array('relation_entity_collector_pick'),
      '#ajax' => array(
        'wrapper' => 'relation_entity_collector_reload',
        'callback' => '_relation_entity_collector_ajax',
      ),
    );
    $form['reload'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Picked entities'),
    );
    $form['reload']['#prefix'] = '<span id="relation_entity_collector_reload">';
    $form['reload']['#suffix'] = '</span>';
    if (!empty($_SESSION['relation_entity_keys'])) {
      $form['reload']['table']['#entity_collector_columns'] = array(
        'weight',
        'remove',
      );
      foreach ($_SESSION['relation_entity_keys'] as $delta => $entity_key) {
        // The structure is (entity_type, entity_id, entity label).
        $form['reload']['table']['weight'][] = array(
          '#type' => 'weight',
          '#delta' => count($_SESSION['relation_entity_keys']),
          '#default_value' => $delta,
          '#title_display' => 'invisible',
          '#title' => '',
        );
        $form['reload']['table']['remove'][] = array(
          '#name' => 'remove-' . $entity_key['entity_key'],
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#entity_key' => $entity_key,
          '#submit' => array('relation_entity_collector_remove'),
          '#ajax' => array(
            'wrapper' => 'relation_entity_collector_reload',
            'callback' => '_relation_entity_collector_ajax',
          ),
        );
        $form['reload']['table']['#tree'] = TRUE;
        $form['reload']['table']['#theme'] = 'relation_entity_collector_table';
      }
      if (!isset($relation_type_object) && !empty($relation_type)) {
        $relation_type_object = RelationType::load($relation_type);
      }
      $min_arity = isset($relation_type_object->min_arity) ? $relation_type_object->min_arity : 1;
      if (count($_SESSION['relation_entity_keys']) >= $min_arity) {
        $form['reload']['save'] = array(
          '#type' => 'submit',
          '#value' => t('Save relation'),
          '#submit' => array('relation_entity_collector_save'),
        );
      }
      if (isset($_SESSION['relation_entity_keys'])) {
        $form['reload']['clear'] = array(
          '#type' => 'submit',
          '#value' => t('Clear'),
          '#submit' => array('relation_entity_collector_clear'),
          '#ajax' => array(
            'wrapper' => 'relation_entity_collector_reload',
            'callback' => '_relation_entity_collector_ajax',
          ),
        );
      }
    }
    $form['explanation'] = array(
      '#prefix' => '<div id=\'relation-entity-collector-explanation\'>',
      '#markup' => t('Picked entities stay in the Entity Collector until cleared or a relation is created so it is possible to collect the entities from several pages.'),
      '#suffix' => '</div>',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state['triggering_element']['#value']) {
      case t('Pick'):
        // Require values.
        $relation_type = $form_state['values']['relation_type'];
        $entity_key = $form_state['values']['entity_key'];
        $errors = FALSE;
        if (empty($relation_type)) {
          form_set_error('relation_type', t('Please select a relation type.'));
          $errors = TRUE;
        }
        if (empty($entity_key)) {
          form_set_error('entity_key', t('Please select an entity.'));
          $errors = TRUE;
        }
        // If either of these are not selected we can not continue.
        if ($errors) {
          return;
        }
        // Get entity info from key ('{entity_type}:{entity_id}').
        list($entity_type, $entity_id) = explode(':', $entity_key);
        // Add the label for later display. #options is check_plain'd but we need
        // to do that ourselves.
        $entity_label = check_plain($form['entity_key']['#options'][$entity_key]);
        // Indexes are added in ascending order, starting from 0.
        $_SESSION += array('relation_entity_keys' => array());
        $next_index = count($_SESSION['relation_entity_keys']);
        // If validation succeeds we will add this in the submit handler.
        $form_state['pick'] = array(
          'r_index'       => $next_index,
          'entity_key'    => $entity_key,
          'entity_label'  => $entity_label,
          'entity_type'   => $entity_type,
          'entity_id'     => $entity_id,
        );
        $endpoints = $_SESSION['relation_entity_keys'];
        $endpoints[] = $form_state['pick'];
        $relation = _relation_entity_collector_get_entity($form_state['values']['relation_type'], $endpoints);
        $relation->in_progress = TRUE;
        _relation_entity_collector_endpoints_validate($relation, $form, $form_state);
        field_attach_form_validate('relation', $relation, $form, $form_state);
        break;

      case t('Save relation'):
        _relation_entity_collector_endpoints_validate(_relation_entity_collector_get_entity(), $form, $form_state);
        break;
    }
  }

  /**
   * Validate relation endpoints.
   */
  public function validateEndpoints($relation, array &$form, FormStateInterface $form_state) {
    // Perform field_level validation.
    try {
      field_attach_validate('relation', $relation);
    }
    catch (FieldValidationException $e) {
      $index = 0;
      // We do not look anything like a field widget so just pile the errors on
      // nonexistent form elements.
      foreach ($e->errors as $field_name => $field_errors) {
        foreach ($field_errors as $langcode => $multiple_errors) {
          foreach ($multiple_errors as $delta => $item_errors) {
            foreach ($item_errors as $item_error) {
              form_set_error('error' . $index++, $item_error['message']);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $relation = _relation_entity_collector_get_entity();
    if ($relation) {
      array_multisort($form_state['values']['table']['weight'], SORT_ASC, $relation->endpoints[Language::LANGCODE_NOT_SPECIFIED]);
      $relation->save();
      if ($relation->id()) {
        $link = \Drupal::l($relation->relation_type->entity->label(), "relation/$relation->id()");
        $list = _relation_stored_entity_keys_list();
        $rendered_list = \Drupal::service('renderer')->render($list);
        $t_arguments = array('!link' => $link, '!list' => $rendered_list);
        if (isset($_SESSION['relation_edit'])) {
          $message = t('Edited !link containing !list', $t_arguments);
        }
        else {
          $message = t('Created new !link from !list', $t_arguments);
        }
        drupal_set_message($message);
        relation_entity_collector_clear($form, $form_state);
      }
      else {
        drupal_set_message(t('Relation not created.'), 'error');
      }
    }
  }

  /**
   * Submit handler for the pick button.
   */
  public function submitPick(array &$form, FormStateInterface $form_state) {
    $_SESSION['relation_entity_keys'][] = $form_state['pick'];
    $_SESSION['relation_type'] = $form_state['values']['relation_type'];
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Submit handler for the remove button.
   */
  public function submitRemove(array &$form, FormStateInterface $form_state) {
    $entity_key = $form_state['triggering_element']['#entity_key']['entity_key'];
    foreach ($_SESSION['relation_entity_keys'] as $key => $entity) {
      if ($entity['entity_key'] == $entity_key) {
        unset($_SESSION['relation_entity_keys'][$key]);
        $form_state['rebuild'] = TRUE;
        return;
      }
    }
  }

  /**
   * Submit handler for the clear button.
   */
  public function submitClear(array &$form, FormStateInterface $form_state) {
    unset($_SESSION['relation_type'], $_SESSION['relation_entity_keys'], $_SESSION['relation_edit']);
    $form_state['rebuild'] = TRUE;
  }

}
