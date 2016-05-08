<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Cache\Cache;
//use Drupal\Core\Cache\CacheBackendInterface;
//use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {
  const GOOGLERESULT = 'Google';
  public function content() {
$config = \Drupal::config('capital_news.settings');
// Will print 'Hello'.
\Drupal::logger('capital-test')->notice(print_r($config->get('search.search_keys')
, true));
// now have some fun with the results...
$news = array();
$keys = explode(",", $config->get('search.search_keys'));
foreach ( $keys as $key){
$this->getNews($news, $key);
\Drupal::logger('capital-test')->notice(print_r($news[0]['#description'],true));
}
return $news;
  }

private function getNews(&$news, $key){
$json = capital_common_get_rest_custom_search($key);
//foreach($json->responseData->results as  $result){
foreach($json->items as  $result){
//foreach($json->data as  $result){
\Drupal::logger('capital-test')->notice(print_r($result->htmlSnippet, true));
  $news[] = array(
        '#type' => 'news_element',
        '#label' => $result->htmlTitle,
        '#description' => str_replace('<br>', '', $result->htmlSnippet),
'#url' => $result->link,
'#news_type' => self:: GOOGLERESULT,
);
}

return $news;
}

}
