<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Controller\ControllerBase;

class NewsTestController extends ControllerBase {

  public function content() {
    _capital_news_get_wechat_news();
            _capital_news_get_google_news(false);

//    capital_common_get_wechat_official_search('sdfasdfads');
    //_capital_news_get_news(false);
    //_capital_news_get_news();
    //   self::createTerms();
    $news=array();
    return $news;
  }
  private function createTerms(){
    $keys = ["www.caixin.com","www.csrc.gov.cn","www.amac.org.cn","www.eeo.com.cn","www.xtxh.net","www.ftchinese.com","www.sse.com.cn","www.szse.cn","www.cnr.cn","www.cctv.com","www.ssf.gov.cn","cn.reuters.com","cn.wsj.com","www.thepaper.cn","www.barrons.com","www.circ.gov.cn","www.sac.net.cn","www.cankaoxiaoxi.com","www.pbc.gov.cn","www.cbrc.gov.cn","www.gov.cn","www.sasac.gov.cn","www.iachina.cn","www.etnet.com.cn","www.yicai.com","www.fx678.com","www.oid.com","www.institutionalinvestorsalpha.com","www.bloomberg.com","www.bbc.com","www.nanzao.com","www.audit.gov.cn","www.ccdi.gov.cn"];
    $keys1 = ["财新网","证监会","基金业协会","经济观察网","信托业协会","FT中文网","上海证券交易所","深圳证券交易所","中央人民广播电台","央视网","全国社保基金理事会","路透中文网","华尔街日报中文网","澎湃新闻网","巴伦周刊","中国保监会","中国证券业协会","参考消息","中国人民银行","中国银监会","中国政府网","国务院国资委","中国保险业协会","经济通","一财网","汇通网","杰出投资者文摘","阿尔法杂志","彭博社","BBC中文网","南华早报中文","中国审计署","中纪委"];
    foreach( $keys as $i => $key){
      self::createOriginTerm($keys1[$i], 'reliable_news_origins', $key);
    }
    $keys = ["www.weibo.com","www.simuwang.com","www.howbuy.com","www.ifeng.com","www.xinhuanet.com","www.stcn.com","www.cs.com.cn","www.cnstock.com","paper.people.com.cn","jjckb.xinhuanet.com","cn.morningstar.com","www.licai.com","www.21jingji.com","www.gw.com.cn","finance.sina.com.cn","news.qq.com","www.caijing.com.cn","www.zhihu.com","news.163.com","wallstreetcn.com","www.chinanews.com","www.hexun.com"];
    $keys1 = ["微博","私募排排网","好买基金网","凤凰网","新华网","证券时报网","中证网","中国证券网","人民日报","经济参考网","晨星","格上理财","21经济网","大智慧","新浪财经","腾讯新闻","财经网","知乎","网易新闻","华尔街见闻","中国新闻网","和讯网"];
    foreach( $keys as $i => $key){
      self::createOriginTerm($keys1[$i], 'other_news_origins', $key);
    }
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
    $keys = explode('，', "谷歌搜索");
    foreach( $keys as $key){
      self::createTerm($key, 'search_type');
    }
  }
  private function createTerm($name, $vid){
    Term::create([
      'name' => $name,
      'vid' => $vid
    ])->save();
  }
  private function createOriginTerm($name, $vid, $link){
    Term::create([
      'name' => $name,
      'vid' => $vid,
      'field_domain_link' =>[
        'value' => $link
      ]

    ])->save();
  }
}
