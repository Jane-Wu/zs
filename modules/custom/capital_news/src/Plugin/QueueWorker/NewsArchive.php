<?php

namespace Drupal\capital_news\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Archive newly linked news nodes.
 *
 * @QueueWorker(
 *   id = "linked_news",
 *   title = @Translation("News archive"),
 *   cron = {"time" = 120}
 * )
 */
class NewsArchive extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $nid = $data;
    _capital_news_save_news_content($nid);
  }
}
