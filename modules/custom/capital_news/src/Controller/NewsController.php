<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {
  public function content() {
$json = capital_common_get_rest_custom_search('私募');
\Drupal::logger('capital-test')->notice(print_r($json, true));
// now have some fun with the results...
$news = array();
//foreach($json->responseData->results as  $result){
foreach($json->items as  $result){
//foreach($json->data as  $result){
  $news[] = array(
        '#type' => 'news_element',
        '#label' => $result->title,
        '#description' => $result->link,
//'#url' => "http://www.drupal.org",
'#url' => $result->link,
);
}

return $news;
  }
}
