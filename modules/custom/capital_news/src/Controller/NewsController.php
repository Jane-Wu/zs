<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {

    public function content() {
        _capital_news_get_news();
        $news=array();
        return $news;
    }
}
