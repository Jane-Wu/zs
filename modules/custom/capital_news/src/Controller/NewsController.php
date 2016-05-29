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
        //_capital_news_get_news();
        //   self::createTerms();
        $news=array();
        return $news;
    }
    private function createTerms(){
        $keys = explode('，', "刘士余，项俊波，周小川，尚福林，洪磊，陈文辉，谢旭仁，李剑阁，尹蔚民，于革胜，李超，吴小晖，杨东，王国彬，李迅雷，陈东升，肖风，朱平，邵健，徐翔，杨明生，姜建清，缪建民，吴清，方星海，肖钢，郭树清，王东明，马明哲，易纲，姜洋，张云，赵欢，吴利军");
        foreach( $keys as $key){
           self::createTerm($key, 'hot_spots');
       }
       $keys = explode('，', "高盛，摩根斯坦利，李录，索罗斯，George Soros，伯克希尔，巴菲特，Buffett，芒格，Charlie Munger，格罗斯，Bill Gross，霍华德，Dwight Howard，桥水，Bridgewater Associates，达里奥，Ray Dalio，耶伦");
        foreach( $keys as $key){
           self::createTerm($key, 'oversee_spots');
       }
       $keys = explode('，', "社保基金，养老金，房价，金价，内幕交易，操纵市场，保险资金，险资，券商资管，注册制，立案调查，资产证券化，金融整顿，去杠杆，权威人士");
        foreach( $keys as $key){
           self::createTerm($key, 'general_key_words');
       }
       $keys = explode('，', "安邦，兴业全球");
        foreach( $keys as $key){
           self::createTerm($key, 'additional_orgs');
       }
    }
    private function createTerm($name, $vid){
             Term::create([
                 'name' => $name,
                  'vid' => $vid
              ])->save();
    }
}
