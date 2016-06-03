<?php

/**
 * @file
 * Contains \Drupal\capital_views\Plugin\views\field\CompanyStaff.
 */

namespace Drupal\capital_views\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("company_staff")
 */
class CompanyStaff extends PrerenderList {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['nid'] = array('table' => 'node_field_data', 'field' => 'nid');
  }

  public function query() {
    $this->addAdditionalFields();
    $this->field_alias = $this->aliases['nid'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['staff_type'] = array(
      'default' => '',
    );

    $options['staff_link'] = array(
      'default' => TRUE,
    );

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = array();
    $query = \Drupal::entityQuery('taxonomy_term')
      // @todo Sorting on vocabulary properties -
      //   https://www.drupal.org/node/1821274.
      ->sort('weight')
      ->sort('name')
      ->addTag('term_access');
    $query->condition('vid', 'staff_type');
    $terms = Term::loadMultiple($query->execute());
    foreach ($terms as $term) {
      $options[$term->id()] = \Drupal::entityManager()->getTranslationFromContext($term)->label();
    }
    $form['staff_type'] = array(
      '#title' => $this->t('Which staff type should be displayed?'),
      '#type' => 'select',
      '#default_value' => $this->options['staff_type'],
      '#options' => $options,
    );

    $form['staff_link'] = array(
      '#title' => $this->t('Link to entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['staff_link'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  public function preRender(&$values) {
    $uids = array();
    $this->items = array();

    foreach ($values as $result) {
      $nids[] = $this->getValue($result);
    }

    if ($nids) {
      $staff_type = $this->options['staff_type'];
      $result = $this->database->query('SELECT c.entity_id as staff_nid, c.field_company_target_id as company_nid, n.title as staff_name FROM {node__field_company} c JOIN {node__field_staff_type} s ON c.entity_id = s.entity_id JOIN {node_field_data} n on c.entity_id = n.nid WHERE c.field_company_target_id IN ( :nids[] ) AND s.field_staff_type_target_id = :tid', array(':nids[]' => $nids, 'tid' => $staff_type));
      foreach ($result as $staff) {
        $this->items[$staff->company_nid][$staff->staff_nid]['staff_name'] = $staff->staff_name;
        $this->items[$staff->company_nid][$staff->staff_nid]['staff_nid'] = $staff->staff_nid;
      }
    }
  }

  function render_item($count, $item) {
    if ($this->options['staff_link']) {
      $url = Url::fromRoute('entity.node.canonical',
        ['node' => $item['staff_nid']]);
      $link = Link::fromTextAndUrl($item['staff_name'], $url);
      return $link->toString();
    }
    else {
      return $item['staff_name'];
    }
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['{{ ' . $this->options['id'] . '__staff_name' . ' }}'] = $this->t('The name of the staff.');
    $tokens['{{ ' . $this->options['id'] . '__staff_nid' . ' }}'] = $this->t('The nid of the staff.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['name'])) {
      $tokens['{{ ' . $this->options['id'] . '__staff_name' . ' }}'] = $item['staff_name'];
      $tokens['{{ ' . $this->options['id'] . '__staff_nid' . ' }}'] = $item['staff_nid'];
    }
  }

}

