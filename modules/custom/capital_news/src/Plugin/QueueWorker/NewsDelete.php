<?php

namespace Drupal\capital_news\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Delete obsolete news nodes.
 *
 * @QueueWorker(
 *   id = "obsolete_news",
 *   title = @Translation("News delete"),
 *   cron = {"time" = 60}
 * )
 */
class NewsDelete extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('capital-news-delete')->debug('Delete news: ' . print_r($data, TRUE));
    entity_delete_multiple('node', $data);
  }

}
