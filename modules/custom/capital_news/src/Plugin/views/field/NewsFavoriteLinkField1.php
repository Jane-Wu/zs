<?php

/**
 * @file
 * Contains \Drupal\capital_views\Plugin\views\field\CompanyStaff.
 */

namespace Drupal\capital_news\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("favorite_link")
 */
class NewsFavoriteLinkField extends FieldPluginBase {
  /*
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['nid'] = array('table' => 'node_field_data', 'field' => 'nid');
  }
   */
  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set the default relationship handler. The first instance of the
    // FlagViewsRelationship should always have the id "flag_relationship", so
    // we set that as the default.
    //$options['relationship'] = array('default' => 'relation_relationship');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  /*
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['relationship']['#default_value'] = $this->options['relationship'];

    parent::buildOptionsForm($form, $form_state);
  }
   */
  public function query() {
  }
  public function preRender(&$values) {
    //\Drupal::logger('ssscapital-test')->debug(print_r($values, true));
    // Render all nodes, so you can grep the comment links.
    /*
        $entities = array();
            foreach ($values as $row) {
                    $entity = $row->_entity;
                          $entities[$entity->id()] = $entity;
                        }
            if ($entities) {
                    $this->build = entity_view_multiple($entities, $this->options['teaser'] ? 'teaser' : 'full');
            }
     */
  }
  public function render(ResultRow $values) {
    //$entity = $this->getParentRelationshipEntity($values);
    //\Drupal::logger('capital-teyyst')->debug(print_r($values, true));
    \Drupal::logger('capital-teyyst')->debug(print_r($values->_entity, true));
    $value = $this->getValue($values);
    \Drupal::logger('capital-teyyst')->debug(print_r($value, true));
    //return print_r($values,true);
    //jj        $entity = $this->getEntity($values);

    // \Drupal::logger('capital-test')->debug(print_r($entity->id(), true));
    //\Drupal::logger('capital-test')->debug(print_r($entity, true));
    return 'asdf';
    //return $this->renderLink($entity, $values);
  }


}

