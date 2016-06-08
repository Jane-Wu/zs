<?php

/**
 * @file
 * Definition of views_handler_field_relation_link_edit.
 */

/**
 * Field handler to present a link relation edit.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_relation_link_edit extends views_handler_field_relation_link {

  /**
   * Renders the link.
   */
  function render_link($relation, $values) {
    // Ensure user has access to edit this relation.
    if (!$relation->access('update')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $relation_id = $relation->id();
    $this->options['alter']['path'] = "relation/$relation_id/edit";
    $this->options['alter']['query'] = \Drupal::destination()->getAsArray();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('Edit');
    return $text;
  }

}
