<?php
 
namespace Drupal\capital_news\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;

class LinkNewsForm extends FormBase {
 
  public function getFormId() {
    return 'link_news_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['node_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Node`s title'),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Load'),
      '#ajax' => array( // here we add Ajax callback where we will process  
        'callback' => '::open_modal',  // the data that came from the form and that we will receive as a result in the modal window
      ),
    );

    $form['#title'] = 'Load node ID';
    return $form;

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('capital_news.settings');

    $form['search_keys'] = array(

      '#type' => 'textarea',

      '#title' => $this->t('Search Keys'),

      '#default_value' => $config->get('search.search_keys'),

      '#required' => TRUE,

    );

    return $form;

  }

  /**

   * {@inheritdoc}

   */

  public function submitForm(array &$form, FormStateInterface $form_state) {


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
