<?php

/**
 * @file
 * Contains \Drupal\relation\Form\RelationTypeDeleteConfirm.
 */

namespace Drupal\relation\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for relation type deletion.
 */
class RelationTypeDeleteConfirm extends EntityConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RelationTypeDeleteConfirm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the relation type %type?', array('%type' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.relation.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_relations = $this->database->query("SELECT COUNT(*) FROM {relation} WHERE relation_type = :type", array(':type' => $this->entity->id()))->fetchField();

    if ($num_relations) {
      $caption = '<p>' . \Drupal::translation()->formatPlural($num_relations, '%relation_type is used by 1 relation. You can not remove this relation type until you have removed all %relation_type relations.', '%relation_type is used by @count relations. You can not remove %relation_type until you have removed all %relation_type relations.', array('%relation_type' => $this->entity->label())) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $t_args = array('%relation_type' => $this->entity->label());
    drupal_set_message(t('The relation type %relation_type has been deleted.', $t_args));
    $this->logger('relation')->notice('Deleted relation type %relation_type.', $t_args);
    $form_state->setRedirect('entity.relation_type.collection');
  }

}
