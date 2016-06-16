<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\dblog\Controller\DbLogController;

class NewsReportController extends DbLogController{
  public function getContent($type) {
    // Keep a backup of the old filer for dblog page
    $old_filter = $_SESSION['dblog_overview_filter'];

    $filter_type = "capital-$type-no-result";
    // Create the filter for our customized type
    // Type can be: google, wechat
    $_SESSION['dblog_overview_filter'] = array(
      'type' => array($filter_type => $filter_type),
      'severity' => array(),
    );
    $content = parent::overview();

    unset($content['dblog_filter_form']);
    unset($content['dblog_clear_log_form']);

    // Only keep the date and content column
    $table = $content['dblog_table'];
    $header = array_slice($table['#header'], 2, 2);
    $rows = $table['#rows'];
    $new_rows = array();
    foreach ($rows as $row) {
      $new_rows[] = array(
        'data' => array_slice($row['data'], 2, 2),
        'class' => $row['class'],
      );
    }
    $content['dblog_table']['#header'] = $header;
    $content['dblog_table']['#rows'] = $new_rows;

    // Set back the old filter for dblog
    $_SESSION['dblog_overview_filter'] = $old_filter;

    return $content;
  }

  public function getTitle($type) {
    $type = strtolower($type);
    if ($type == 'google') {
      return '谷歌搜索无内容关键词';
    }
    elseif ($type == 'wechat') {
      return '微信搜索无内容关键词';
    }
    else {
      return '';
    }
  }
}
