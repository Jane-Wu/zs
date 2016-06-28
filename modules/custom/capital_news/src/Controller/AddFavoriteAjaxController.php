<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Cache\Cache;
//use Drupal\Core\Cache\CacheBackendInterface;
//use Drupal\Core\Controller\ControllerBase;

class AddFavoriteAjaxController extends ControllerBase {
  public function content() {
\Drupal::logger('capital-test')->notice(print_r('ajax work', true));
return array();
}

}
