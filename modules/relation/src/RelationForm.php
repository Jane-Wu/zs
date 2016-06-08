<?php

/**
 * @file
 * Definition of Drupal\relation\RelationForm.
 */

namespace Drupal\relation;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for relation edit.
 */
class RelationForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $relation = $this->getEntity();
    $element = parent::actions($form, $form_state);
    $element['delete']['#access'] = $relation->access('delete');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $relation = $this->getEntity();
    $relation->save();
    $form_state->setRedirectUrl($relation->urlInfo());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $relation = $this->getEntity();

    if ($this->operation == 'edit') {
      $form['#title'] = t('<em>Editing</em> @label', array('@label' => $relation->label()));;
    }

    return parent::form($form, $form_state, $relation);
  }

}
