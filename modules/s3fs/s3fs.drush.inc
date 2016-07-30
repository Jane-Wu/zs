<?php

/**
 * @file
 * Defines a drush command that refreshes the S3 metadata cache.
 */

/**
 * Implements hook_drush_command().
 */
function s3fs_drush_command() {
  $items = array();

  $items['s3fs-copy-local'] = array(
    'description' => 'Copy local files from your public and/or private file system(s) into your S3 bucket, if s3fs is configured to take them over.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('s3fs-cl'),
    'callback' => 'drush_s3fs_copy_local',
    'examples' => array(
      'drush s3fs-copy-local' => 'Copy local files from your public and/or private file system(s) into your S3 bucket.',
    ),
  );

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 *
 * Copies all files from the local public/private filesystem folders into S3,
 * if s3fs is configured to take over those systems.
 */
function drush_s3fs_copy_local() {
  $config = \Drupal::config('s3fs.settings')->getRawData();
  if (!\Drupal::service('s3fs.validate')->validate($config)) {
    drupal_set_message(
      t('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.'),
      'error'
    );
    return;
  }
  if (empty($config['use_s3_for_public']) && empty($config['use_s3_for_private'])) {
    drupal_set_message(
      t('S3 File System is not configured to take over any other file systems.')
    );
    return;
  }
  if (!empty($config['use_s3_for_public'])) {
    \Drupal::service('s3fs.validate')->copyFileSystemToS3($config, 'public');
  }
  if (!empty($config['use_s3_for_private'])) {
    \Drupal::service('s3fs.validate')->copyFileSystemToS3($config, 'private');
  }
}
