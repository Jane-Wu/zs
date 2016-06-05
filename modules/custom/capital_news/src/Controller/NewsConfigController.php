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
    $vids = ['wechat_accounts', 'additional_orgs', 'hot_spots', 'oversee_spots', 'general_key_words'];
    //$names = ["权威来源","线索来源","其他微信公众号","其他机构","热门人物","海外来风","宽泛关键词"];
    $blocks = [];
    foreach( $vids as $vid_key) {
      $block_id = 'views_block__news_keywords_management_' . $vid_key;
      $blocks[] = self::get_block_content($block_id);
    }
    $blocks[] = self::get_block_content(
      'views_block__company_related_news_keywords_block');
    return $blocks;
  }

  private function get_block_content($block_id) {
    $block = \Drupal\block\Entity\Block::load($block_id);
    $block_content = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);
    return $block_content;
  }
}
