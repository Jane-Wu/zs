<?php

namespace Drupal\capital_news;

use Drupal\relation\Entity\Relation;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

class LinkNewsNodeLink {
  public $rids;
  //public $src_bundle;
  public $nid;
  public $news_id;

  public function __construct($news_id, $nid = null){//, $src_bundle){
    $this->news_id = $news_id;
    $this->nid = $nid;
//    $this->src_bundle = $src_bundle;
  }

  public function exists(){
    $query = \Drupal::database()->select('relation__endpoints', 'endpoint');
    $query->fields('endpoint', ['entity_id']);
    $query->join('relation__endpoints', 'endpoint2', 'endpoint.entity_id = endpoint2.entity_id');
    $query->condition('endpoint.bundle', 'link_news');
    $query->condition('endpoint.endpoints_entity_type', 'node');
    $query->condition('endpoint2.endpoints_entity_type', 'node');
    $query->condition('endpoint.endpoints_entity_id', $this->nid);
    $query->condition('endpoint2.endpoints_entity_id', $this->news_id);
    $rids = $query->execute()->fetchAllAssoc('entity_id');
    $rids = array_keys($rids);

    $this->rid = $rids;

    return $rids;
  }
  public function getLink(){
    return empty($this->exists())? $this->getRemoveLink(): $this->getAddLink();
  }
  public function getListLink(){
    $link = [
      '#type' => 'link',
      '#title' => ' ',
      '#url' => $this->getListUrl(),
      '#attributes' => [
        'class' => 'use-ajax glyphicon glyphicon-link',
        'id' => 'capital-link-news-' . $this->news_id,
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],

    ];
    return $link;
    return empty($this->exists())? $this->getRemoveLink(): $this->getAddLink();
  }
  public function getAddLink(){
    $link = [
      '#type' => 'link',
      '#title' => 'sdf',
      '#url' => $this->getUrl(),
      '#attributes' => [
        'class' => 'use-ajax glyphicon glyphicon-link',
        'id' => 'capital-favorite-news-' . $this->news_id,
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    return $link;
  }
  public function getUrl(){
    return Url::fromRoute('capital_news.linkform', ['nid' => 121, 'news_id' => $this->news_id]);
    return Url::fromRoute('capital_news.linknews', ['nid' => $this->nid, 'news_id' => $this->news_id]);
  }
  public function getListUrl(){
    return Url::fromRoute('capital_news.linkform', ['news_id' => $this->news_id]);
    return Url::fromRoute('capital_news.getlist', ['news_id' => $this->news_id]);
  }
  public function getRemoveLink(){
    $link = [
      '#type' => 'link',
      '#title' => ' ',
      '#url' => $this->getUrl(),
      '#attributes' => [
        'class' => 'use-ajax glyphicon glyphicon-link',
        'id' => 'capital-favorite-news-' . $this->news_id,
      ],
    ];
    return $link;
  }
  public function create(){
    createRelation('link_news', 'node', $this->nid, 'node', $this->news_id);
  }
  public function remove(){
    removeRelation($this->rids);
  }
}
