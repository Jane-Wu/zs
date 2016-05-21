<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {

  public function content() {
    $config = \Drupal::config('capital_news.settings');
      $results = $config->get('search.result');
      //$results = _capital_news_getnews();
$news=array();
$current_time = time();
      foreach ( $results as $new){
\Drupal::logger('capital-test')->notice(print_r($current_time, true));
\Drupal::logger('capital-test')->notice(print_r($new, true));
preg_match('/^(\d+) (hours|day) ago/', $new['description'], $matches);
if (count($matches) != 3){

\Drupal::logger('capital-test')->warning(print_r($matches, true));
}
$approximate_timestamp = $current_time - ($matches[2] == 'hours'? 3600 * $matches[1] : 86400);
$new['approximate_timestamp'] = $approximate_timestamp ;
$news[] =  [
        '#type' => 'news_element',
        '#label' => $new['label'],
        '#description' => $new['description'],
        //'#description' => $approximate_timestamp,
        '#url' => $new['url'],
        '#news_type' => $new['news_type'],
        ];

break;
    }
    return $news;
  }
}
