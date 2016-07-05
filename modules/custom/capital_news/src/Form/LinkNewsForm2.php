<?php
 
namespace Drupal\capital_news\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Element\EntityAutocomplete;


class LinkNewsForm2 extends FormBase {
 
  public function getFormId() {
    return 'link_news_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    //$response = new AjaxResponse();
    $news_id = \Drupal::request()->get('news_id');
    $title = '收藏新闻';
    if ($news_id !== NULL) {
      $news = Node::load($news_id);
      $related_nid_fields = $news->get('field_related_nodes')->getValue();
      $options = [];
      foreach($related_nid_fields as $related_nid_field){
        $node = Node::load($related_nid_field['target_id']);
        //$nodeType = NodeType::load($node->getType());
        $options[$related_nid_field['target_id']] = $node->type->entity->label() . ': ' . $node->getTitle();
      }
      \Drupal::logger('capital-test')->debug(print_r($options, true));


      $form = [
        '#type' => 'container',
        '#attributes' => [
          //   'class' => 'accommodation',
        ],
        'recommendation' => [
          '#type' => 'checkboxes',
          '#options' => $options,
          '#title' => $this->t('推荐收藏公司/人员'),
          '#required' => false,
        ],
        'company' => [
          //'#type' => 'fieldset',
          //'#title' => $this->t('Book Info (type = fieldset)'),
          '#type' => 'textfield',
          //'#autocomplete_route_name' => 'user.autocomplete',
          '#type' => 'entity_autocomplete',
          '#title' => t('choose users'),
          '#target_type' => 'user',
          //'#default_value' =>
        ],
      ];
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    /*
    $form['node_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Node`s title'),
    );
     */
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Load'),
    );

    return $form;
  }

  /**

   * {@inheritdoc}

   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
      \Drupal::logger('capital-test')->debug(print_r($form_state, true));


  }
  public function open_modal(&$form, FormStateInterface $form_state) {
    $node_title = $form_state->getValue('node_title');
    $query = \Drupal::entityQuery('node')
      ->condition('title', $node_title);
    $entity = $query->execute();
    $key = array_keys($entity);
    $id = !empty($key[0]) ? $key[0] : NULL;
    $response = new AjaxResponse();
    $title = 'Node ID';
    if ($id !== NULL) {
      $content = '<div class="test-popup-content"> Node ID is: ' . $id . '</div>';
      $options = array(
        'dialogClass' => 'popup-dialog-class',
        'width' => '300',
        'height' => '300',
      );
      $response->addCommand(new OpenModalDialogCommand($title, $content, $options));
    }
    else {
      $content = 'Not found record with this title <strong>' . $node_title .'</strong>';
      $response->addCommand(new OpenModalDialogCommand($title, $content));
    }
    return $response;
  }
}
