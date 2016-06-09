<?php

namespace Drupal\capital_news;

use Drupal\relation\Entity\Relation;
use Drupal\Core\Url;

class FavoriteNewsLink {
  public $rid;
  public $user_id;
  public  $news_id;

  public function __construct($news_id){
    $this->news_id = $news_id;
    $this->user_id = \Drupal::currentUser()->id();
  }

  public function exists(){
    $query = \Drupal::database()->select('relation__endpoints', 'endpoint');
    $query->fields('endpoint', ['entity_id']);
    $query->join('relation__endpoints', 'endpoint2', 'endpoint.entity_id = endpoint2.entity_id');
    $query->condition('endpoint.bundle', 'user_favorite_news');
    $query->condition('endpoint.endpoints_entity_type', 'user');
    $query->condition('endpoint2.endpoints_entity_type', 'node');
    $query->condition('endpoint.endpoints_entity_id', $this->user_id);
    $query->condition('endpoint2.endpoints_entity_id', $this->news_id);
    $rids = $query->execute()->fetchAllAssoc('entity_id');
    $rids = array_keys($rids);

    $this->rid = $rids;

    return $rids;
  }
  public function getLink(){
    return empty($this->exists())?  $this->getAddLink() : $this->getRemoveLink();
  }
  public function getAddLink(){
    $link = [
      '#type' => 'link',
      '#title' => ' ',
      '#url' => $this->getUrl(),
      '#attributes' => [
        'class' => 'use-ajax glyphicon glyphicon-star-empty',
        'id' => 'capital-favorite-news-' . $this->news_id,
      ],

    ];
    return $link;
  }
  public function getAddUrl(){
    return Url::fromRoute('capital_news.addfavoritenews', ['nid' => $this->news_id]);
  }
  public function getUrl(){
    return Url::fromRoute('capital_news.favoritenews', ['nid' => $this->news_id]);
  }
  public function getRemoveLink(){
    $link = [
      '#type' => 'link',
      '#title' => ' ',
      '#url' => $this->getUrl(),
      '#attributes' => [
        'class' => 'use-ajax glyphicon glyphicon-star',
        'id' => 'capital-favorite-news-' . $this->news_id,
      ],
    ];
    return $link;
  }
  public function getRemoveUrl(){
    return Url::fromRoute('capital_news.removefavoritenews', ['rid' => $this->rid]);
  }
  public function create(){
    createRelation('user_favorite_news', 'user', $this->user_id, 'node', $this->news_id);
  }
  public function remove(){
    removeRelation($this->rid);
  }
}
