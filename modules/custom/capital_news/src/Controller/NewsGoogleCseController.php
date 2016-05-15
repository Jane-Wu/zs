<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Controller\ControllerBase;

class NewsGoogleCseController extends ControllerBase {
  const WECHATRESULT = 'Wechat';
  public function content() {

    $config = \Drupal::config('capital_news.settings');
    if($config->get('search.cache_wechat') == null ){
      \Drupal::logger('capital-test')->notice(print_r('live wecht', true));
      $url = 'http://weixin.sogou.com/weixin?type=1&query=%E8%81%AA%E6%98%8E%E6%8A%95%E8%B5%84%E8%80%85';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_REFERER, "capital-dev");
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $body = curl_exec($ch);
      curl_close($ch);

      \Drupal::service('config.factory')->getEditable('capital_news.settings')->set('search.cache_wechat', $body)->save();
    }
    
    $body=$config->get('search.cache_wechat'); 
    \Drupal::logger('capital-test')->notice(print_r($body, true));
    $pattern = '/target.+Capital-nature/';
    $pattern = '/gotourl\(\'(.+)\'/';
    preg_match($pattern, $body, $matches);
    print_r($matches,true);
    \Drupal::logger('capital-test')->notice(print_r($matches, true));
    \Drupal::logger('capital-test')->notice(print_r($matches[1], true));
    $url = $matches[1];
    $url = 'http://mp.weixin.qq.com/profile?src=3&timestamp=1463218068&ver=1&signature=fWXHUxx6IWHM6-lIzQBwEgQ38oixbZNtZq9*Yl5Xg9iV2YmZYL96DKoW9dekBugHDOIaDw33JufEmVcuHgZTAA==';
    $url = 'http://mp.weixin.qq.com/profile?src=3&timestamp=1463299468&ver=1&signature=fWXHUxx6IWHM6-lIzQBwEgQ38oixbZNtZq9*Yl5Xg9iV2YmZYL96DKoW9dekBugHQeM-ApwpwV*b6hegiWW1hA==';
    if($config->get('search.cache_wechat1') == null ){
    \Drupal::logger('capital-test')->notice(print_r('live wecht1', true));
    $ch = curl_init();
    $ip = '54.12.32.45';
    $headers=array(
        'X-FORWARDED-FOR:'.$ip,
        'CLIENT-IP:'.$ip,
        'Accept-Language: zh-cn',
        'Accept-Encoding:gzip,deflate',
        'Connection: Keep-Alive',
        'Cache-Control: no-cache'
        );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION,$foll); // 使用自动跳转
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($ch, CURLOPT_HTTPGET, 1); // 发送一个常规的Post请求
    //curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookie_file']); // 存放Cookie信息的文件名称
    //curl_setopt($ch, CURLOPT_COOKIEFILE,$GLOBALS ['cookie_file']); // 读取上面所储存的Cookie信息
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');//解释gzip
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($ch, CURLOPT_REFERER, "capital-dev");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    curl_close($ch);

    \Drupal::service('config.factory')->getEditable('capital_news.settings')->set('search.cache_wechat1', $body)->save();
    }
    $body=$config->get('search.cache_wechat1'); 
    \Drupal::logger('capital-test')->notice(print_r($body, true));
    $pattern = "/msgList.*'(.*)'/";
    preg_match($pattern, $body, $matches);
    print_r($matches,true);
    \Drupal::logger('capital-test')->notice(print_r($matches, true));
    $search = ['&quot;', 'amp;'];
    $replace = ['"', ''];
    $json = json_decode(str_replace($search, $replace,$matches[1]));
    \Drupal::logger('capital-test')->notice(print_r($json->list, true));
    \Drupal::logger('capital-test')->notice(print_r($json->list[0], true));
    //\Drupal::logger('capital-test')->notice(print_r($matches[1], true));
$news = array();
foreach($json->list as $new){

      $news[] = [
        '#type' => 'news_element',
        '#label' => $new->app_msg_ext_info->title,
        '#description' => $new->app_msg_ext_info->digest,
        '#url' => 'http://mp.weixin.qq.com' . substr($new->app_msg_ext_info->content_url, 1),
        '#url' => 'http://mp.weixin.qq.com/s?timestamp=1463299500&src=3&ver=1&signature=qs84uLaY3nUis*7gLAjZ2nPsTWDNPYaOY0aOLUc7ihGDzzKtV3MktFqR4GnILzJ1Vtb7QkqJKvew3Q*w*ZwH6rphicmiFtoiuztAhBtNWRuIIGIoDuSGrTtZ-WeZVFZCDdAIDhXFco7luiQ0IM4fId17F11RK0FLBg1A7BeeJcI=',
        '#news_type' => self:: WECHATRESULT
        ];
}
    return $news;
  }
}
