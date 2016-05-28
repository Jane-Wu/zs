<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {

    public function content() {
        $a = explode('，', "刘士余，项俊波，周小川，尚福林，洪磊，陈文辉，谢旭仁，李剑阁，尹蔚民，于革胜，李超，吴小晖，杨东，王国彬，李迅雷，陈东升，肖风，朱平，邵健，徐翔，杨明生，姜建清，缪建民，吴清，方星海，肖钢，郭树清，王东明，马明哲，易纲，姜洋，张云，赵欢，吴利军");
        foreach( $a as $t){
            createTerm($t, 'key_words');
       }
        $news=array();
        return $news;
    }
    private createTerm($name, $vid){
             Term::create([
                 'name' => $name,
                  'vid' => $vid
              ])->save();
    }
}
