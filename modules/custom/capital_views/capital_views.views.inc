<?php

/**
 * Implements hook_views_data().
 */
function capital_views_views_data() {
  $data['node']['company_staff'] = array(
    'title' => t('Company Staff'),
    'field' => array(
      'title' => t('Company Staff'),
      'help' => t('Provides the staff list for the company'),
      'id' => 'company_staff',
    ),
  );

  $data['views']['view_area_button_area'] = array(
    'title' => t('Button'),
    'help' => t('A configurable button for view areas like the header or footer. Could be used to provide an Add button. Has a configurable label, destination URI, and permission-based visibility.  An Add button can use Prepopulate module via the URI.'),
    'area' => array(
      'id' => 'view_area_button_area',
    ),
  );
  return $data;
}
