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
      //$news = $config->get('search.result');
      $results = _capital_news_getnews();
$news=array();
      foreach ( $results as $new){
$news[] =  [
        '#type' => 'news_element',
        '#label' => $new['label'],
        '#description' => $new['description'],
        '#url' => $new['url'],
        '#news_type' => $new['news_type'],
        ];

    }
    return $news;
  }
}
