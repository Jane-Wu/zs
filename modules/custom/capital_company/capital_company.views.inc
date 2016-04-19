<?php

/**
 * Implements hook_views_data_alter().
 */
function capital_company_views_data_alter(array &$data) {
  $data['node']['company_staff'] = array(
    'title' => t('Company Staff'),
    'field' => array(
      'title' => t('Company Staff'),
      'help' => t('Provides the staff list for the company'),
      'id' => 'company_staff',
    ),
  );
}
