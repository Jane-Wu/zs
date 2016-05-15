<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {
  const GOOGLERESULT = 'Google';

  public function content() {
    $config = \Drupal::config('capital_news.settings');
    //if($config->get('search.result') == null || $config->get('search.cache_keys') != $config->get('search.search_keys')){
    if($config->get('search.result') == null ){

      \Drupal::logger('capital-test')->notice(print_r('live search', true));

      \Drupal::logger('capital-test')->notice(print_r($config->get('search.search_keys'), true));
      $keys = explode('，', $config->get('search.search_keys'));
      $news = array();
      foreach ( $keys as $key){
        $this->getGoogleNews($news, trim($key));
      }
      //\Drupal::service('config.factory')->getEditable('capital_news.settings')->set('search.cache_keys', $config->get('search.search_keys'))->save();
      \Drupal::service('config.factory')->getEditable('capital_news.settings')->set('search.result', $news)->save();
    }
    return $config->get('search.result');
  }

  private function getGoogleNews(&$news, $key){
    $json = capital_common_get_rest_custom_search($key);
    foreach($json->items as $result){
      $news[] = [
        '#type' => 'news_element',
        '#label' => $result->htmlTitle,
        '#description' => str_replace('<br>', '', $result->htmlSnippet),
        '#url' => $result->link,
        '#news_type' => self:: GOOGLERESULT
        ];
    }
    return $news;
  }
}
