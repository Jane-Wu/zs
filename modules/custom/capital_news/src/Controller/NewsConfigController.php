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
    #$names = ["权威来源","线索来源","其他微信公众号","其他机构","热门人物","海外来风","宽泛关键词"];
    $blocks = [];
    foreach( $vids as $vid_key) {
      $block = \Drupal\block\Entity\Block::load('views_block__key_word_manage_' . $vid_key);
      $block_content = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block);
      $blocks[] = $block_content;
    }
    return $blocks;
  }
}
