<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Plugin\Field\FieldWidget\RelationEndpointWidget.
 *
 * TODO: Figure out if there is easier way to say "no we don't have edit widget"
 */

namespace Drupal\relation_endpoint\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'relation_endpoint' widget.
 *
 * @FieldWidget(
 *   id = "relation_endpoint",
 *   label = @Translation("No widget"),
 *   field_types = {
 *     "relation_endpoint",
 *   }
 * )
 */
class RelationEndpointWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return array();
  }

}
