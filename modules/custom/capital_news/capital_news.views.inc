<?php

/**
 * Implements hook_views_data().
 */
function capital_news_views_data() {
  $data['node']['favorite_link'] = array(
    'title' => t('Favorite Link'),
    'field' => array(
      'title' => t('Favorite Link'),
      'help' => t('Provide the add/remove favorite link'),
      'id' => 'favorite_link',
    ),
  );

  $data['node']['linknode_link'] = array(
    'title' => t('Link Node Link'),
    'field' => array(
      'title' => t('Link Node Link'),
      'help' => t('Provide the add/remove news and company/staff link'),
      'id' => 'linknode_link',
    ),
  );

  return $data;
}
