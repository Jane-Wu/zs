<?php

namespace Drupal\capital_news;

use Drupal\relation\Entity\Relation;

class NewsLink {
  public function createRelation($relation_type, $src_type, $src_id, $target_type, $target_id){
    $endpoints = [
      [
        'entity_type' => $src_type,
        'entity_id'   => $src_id,
        'r_index'     => 0,
      ],
      [
        'entity_type' => $target_type,
        'entity_id'   => $target_id,
        'r_index'     => 1,
      ],
    ];

    $relation = Relation::create(array('relation_type' => $relation_type));
    $relation->endpoints = $endpoints;
    $relation->save();
    \Drupal::logger('capital-relation')->notice('Create relation ' . print_r($relation_type, true));
  }
}
