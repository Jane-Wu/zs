<?php
/**
 * @file
 * Contains \Drupal\capital_news\Controller\NewsController.
 */

namespace Drupal\capital_news\Controller;

use Drupal\Core\Controller\ControllerBase;

class NewsController extends ControllerBase {
  public function content() {
// The request also includes the userip parameter which provides the end
// user's IP address. Doing so will help distinguish this legitimate
// server-side traffic from traffic which doesn't come from an end-user.
$url = "https://ajax.googleapis.com/ajax/services/search/web?v=1.0&"
    . "q=" . urlencode("私募基金") . "&userip=USERS-IP-ADDRESS&rsz=20";
$url = "https://api.wmcloud.com:443/data/v1/api/market/getMktEqud.json?field=&beginDate=20150101&endDate=&secID=&ticker=000001&tradeDate=";

// sendRequest
// note how referer is set manually
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_REFERER, "capital-dev");
$headers = array();
$headers[] = 'Authorization: Bearer bd0f3acf153580238b16ffb84edd1e89304e5e59f33e8b2eab271ba90e003b95';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$body = curl_exec($ch);
curl_close($ch);

// now, process the JSON string
$json = json_decode($body);
\Drupal::logger('my_module')->notice(print_r($json->data, true));
// now have some fun with the results...
$news = array();
//foreach($json->responseData->results as  $result){
foreach($json->data as  $result){
  $news[] = array(
        '#type' => 'news_element',
        '#label' => $result->secShortName,
        '#description' => $result->secShortName,
'#url' => "http://www.drupal.org",
);
}

return $news;
  }
}
