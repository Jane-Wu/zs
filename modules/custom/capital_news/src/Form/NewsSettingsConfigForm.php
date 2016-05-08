<?php
 
/**
 
 * @file
 
 
 */
 
namespace Drupal\capital_news\Form;
 
use Drupal\Core\Form\ConfigFormBase;
 
use Drupal\Core\Form\FormStateInterface;
 
class NewsSettingsConfigForm extends ConfigFormBase {
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function getFormId() {
 
    return 'capital_news_settings_config_form';
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form = parent::buildForm($form, $form_state);
 
    $config = $this->config('capital_news.settings');
 
    $form['search_keys'] = array(
 
      '#type' => 'textfield',
 
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
 
    $config = $this->config('capital_news.settings');
 
    $config->set('search.search_keys', $form_state->getValue('search_keys'));
 
 
    $config->save();
 
    return parent::submitForm($form, $form_state);
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  protected function getEditableConfigNames() {
 
    return [
 
      'capital_news.settings'
 
    ];
 
  }
 
}
