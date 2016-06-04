<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

class NewsConfigController extends ControllerBase {
  public function content() {
    $vids = ['reliable_news_origins', 'other_news_origins', 'wechat_accounts', 'additional_orgs', 'hot_spots', 'oversee_spots', 'general_key_words'];
    $names = ["权威来源","线索来源","其他微信公众号","其他机构","热门人物","海外来风","宽泛关键词"];
    $links = [];
    foreach( $vids as $key => $vid){
      
      $links[] = self::createLink($vid, $names[$key]);
    }
    return $links;
  }

  private function createLink($vid, $name) {
    //$vocabulary = Vocabulary::load($vid);
    $link = [
      '#type' => 'container',
      'link' => [
        '#type' => 'link',
        '#title' => '编辑' . $name,
        '#url' =>  Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vid])
      ]
    ];
    return $link;
  }
}
