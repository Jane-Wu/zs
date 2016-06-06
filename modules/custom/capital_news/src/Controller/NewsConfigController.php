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
  public function googleConfig() {
    $vids = ['additional_orgs', 'hot_spots', 'oversee_spots', 'general_key_words'];
    //$names = ["其他机构","热门人物","海外来风","宽泛关键词"];
    $blocks = [];
    foreach( $vids as $vid_key) {
      $block_id = 'views_block__news_keywords_management_' . $vid_key;
      $blocks[] = self::getBlockContent($block_id);
    }
    $blocks[] = self::getBlockContent(
      'views_block__company_related_news_keywords_block');
    return $blocks;
  }

  public function officialConfig() {
    $blocks = [];
    $blocks[] = self::getBlockContent(
      'views_block__news_keywords_management_wechat_accounts');
    $blocks[] = self::getBlockContent(
      'views_block__company_related_news_keywords_wechat');
    return $blocks;
  }

  public function wechatConfig() {
    $blocks = [];
    $blocks[] = self::getBlockContent(
      'views_block__news_keywords_management_wechat_keywords');
    $blocks[] = self::getBlockContent(
      'views_block__company_related_news_keywords_block');
    return $blocks;
  }
  private function getBlockContent($block_id) {
    $block = \Drupal\block\Entity\Block::load($block_id);
    $block_content = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);
    return $block_content;
  }
}
