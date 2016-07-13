<?php
 
namespace Drupal\capital_news\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\capital_news\LinkNewsNodeLink;

class LinkNewsForm2 extends FormBase {
 
  public function getFormId() {
    return 'link_news_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    //$response = new AjaxResponse();
    $news_id = \Drupal::request()->get('news_id');
    $title = '收藏新闻 ';
    if ($news_id !== NULL) {
      $news = Node::load($news_id);
      $title .= $news->getTitle();

      $link = new LinkNewsNodeLink($news_id);
      $linked_nids = $link->getParentNids();
      $linked_nodes = [];
      foreach($linked_nids as $linked_nid){
        $linked_nodes[] = Node::load($linked_nid)->getTitle();
      }
      $related_nid_fields = $news->get('field_related_nodes')->getValue();
      $options = [];
      foreach($related_nid_fields as $related_nid_field){
        $node = Node::load($related_nid_field['target_id']);
        $options[$related_nid_field['target_id']] = $node->type->entity->label() . ': ' . $node->getTitle();
      }
      \Drupal::logger('capital-test')->debug(print_r($options, true));

      $form = [
        '#type' => 'container',
        'linked' => [
          '#theme' => 'item_list',
          '#items' => $linked_nodes,
          '#title' => $this->t('已关联的内容'),
        ],
        'recommendation' => [
          '#type' => 'checkboxes',
          '#options' => $options,
          '#title' => $this->t('推荐收藏公司/人员'),
        ],
        'other_nodes' => [
          '#type' => 'entity_autocomplete',
          '#title' => $this->t('关联到其他内容'),
          '#bundles' => array('company'),
          //'#autocreate' => ['bundle' => 'company'],
          '#target_type' => 'node',
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
      '#value' => $this->t('关联'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    );

    return $form;
  }
  /**
   * Implements the sumbit handler for the ajax call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $news_id = \Drupal::request()->get('news_id');
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $selector = '#capital-link-news-' . $news_id;
    $response->addCommand(new InvokeCommand($selector, "addClass", ["glyphicon-ok"]));
    $response->addCommand(new InvokeCommand($selector, "removeClass", ["glyphicon-plus"]));
    return $response;
  }

  /**

   * {@inheritdoc}

   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //drupal_set_message(t('You specified a title of %title.', ['%title' => 'asdf']));
    $news_id = \Drupal::request()->get('news_id');
    \Drupal::logger('capital-ttest')->debug(print_r($news_id, true));
    $values = $form_state->getValues();
    foreach($values['recommendation'] as $nid){
      if ($nid != 0){
        $link = new LinkNewsNodeLink($news_id, $nid);
        $link->checkAndCreate();
      }
    }

    if($values['other_nodes']){
      $link = new LinkNewsNodeLink($news_id, $values['other_nodes']);
      $link->checkAndCreate();
    }
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
