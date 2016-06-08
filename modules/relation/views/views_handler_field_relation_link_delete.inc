<?php

/**
 * @file
 * Definition of views_handler_field_relation_link_delete.
 */

/**
 * Field handler to present a link to delete a relation.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_relation_link_delete extends views_handler_field_relation_link {

  /**
   * Renders the link.
   */
  function render_link($relation, $values) {
    // Ensure user has access to delete this relation.
    if (!$relation->access('delete')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $relation_id = $relation->id();
    $this->options['alter']['path'] = "relation/$relation_id/delete";
    $this->options['alter']['query'] = \Drupal::destination()->getAsArray();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('Delete');
    return $text;
  }

}
