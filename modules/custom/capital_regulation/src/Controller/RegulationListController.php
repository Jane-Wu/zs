<?php
/**
 * @file
 * Contains \Drupal\capital_regulation\Controller\RegulationListController.
 */

namespace Drupal\capital_regulation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

class RegulationListController extends ControllerBase {
  public function getContent() {
    $blocks = array();
    for ($i = 1; $i < 4; $i++) {
      $blocks[] = self::getBlockContent(
        'views_block__regulations_block_' . $i);
    }
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
