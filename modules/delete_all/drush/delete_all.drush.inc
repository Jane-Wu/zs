<?php

/**
 * @file
 * delete all Drush command
 */

use Drupal\delete_all\Controller\UserDeleteController;
use Drupal\delete_all\Controller\ContentDeleteController;

// Include dependencies.
include_once drupal_get_path('module', 'delete_all') . '/includes/delete_all.user.batch.inc';
include_once drupal_get_path('module', 'delete_all') . '/includes/delete_all.content.batch.inc';

/**
 * Implements hook_drush_command().
 *
 * delete-all-delete-user (dadu)
 *   - Command to delete users.
 *   - "--roles" is an optional parameter here and provides the option to delete
 *       all users of a role specified in the command.
 *   - Following are the possible ways to use the command:
 *     1. `drush dadu`
 *          - Deletes all users.
 *     2. `drush dadu --role`
 *          - User can choose one of the roles from the options given. All users
 *            with that role will be deleted.
 *     3. `drush dadu --role role_1,role_2,role_3`
 *          - All users with either of the given roles will be deleted.
 *            Please note that Role names can have black spaces between them,
 *            but two roles should be separed only by a 'comma' without any
 *            space before or after the comma.
 *
 * delete-all-delete-content (dadc)
 *   - Command to delete content. *
 *   - "--type" is an optional parameter here and provides the option to delete
 *       all contents of a type specified in the command. *
 *   - Following are the possible ways to use the command: *
 *     1. `drush dadc`
 *          - Deletes all content of all types.
 *     2. `drush dadc --type`
 *          - User can choose one of the content type from the options given.
 *            All content of that type will be deleted.
 *     3. `drush dadc --type type_1,type_2,type_3`
 *          - All content with either of the given types will be deleted.
 *            Please note that machine name of the content type must be
 *            provided. Two content types should be separed only by a 'comma'
 *            without any space before or after the comma.
 */
function delete_all_drush_command() {
  $items = array();

  $items['delete-all-delete-users'] = array(
    'description' => 'Delete users.',
    'options' => array(
      'role' => 'pick roles', // List of roles should be comma separated.
    ),
    'examples' => array(
      'drush delete-all-delete-users' => 'Delete all users.',
    ),
    'aliases' => array('dadu'),
  );

  $items['delete-all-delete-content'] = array(
    'description' => 'Delete content.',
    'options' => array(
      'type' => 'pick content type',
    ),
    'examples' => array(
      'drush delete-all-delete-content' => 'Delete content.',
    ),
    'aliases' => array('dadc'),
  );
  return $items;
}

/**
 * Drush callback to delete users.
 */
function drush_delete_all_delete_users() {
  // Initialize $roles as FALSE to specify that all users should be deleted.
  // This will be overriden if user provides/choses a role.
  $input_roles = FALSE;

  $deleteUser = new UserDeleteController();

  // Check for presence of '--roles' in drush command.
  if (drush_get_option('role')) {
    // func_get_args collects all keywords separated by space in an array.
    // To get the roles, we join all the keywords in a string and then use
    // 'comma' to separate them.
    $types = func_get_args();
    if ($types) {
      $input_roles = implode(' ', $types);
      if (strpos($input_roles, ',')) {
        $input_roles = explode(',', $input_roles);
      }
      else {
        $input_roles = array($input_roles);
      }
    }
    else {
      $choices = [];
      // Output all roles on screen and ask user to choose one.
      $roles = user_roles();
      foreach ($roles as $key => $value) {
        $choices[$key] = $value->label();
      }
      $role = drush_choice($choices, dt("Choose a role to delete."));

      // Return if no role is chosen.
      if ($role === 0) {
        return;
      }
      $input_roles = array($role);
    }
  }

  // Get users to delete.
  $users_to_delete = $deleteUser->getUserToDelete($input_roles);

  // Get batch array.
  $batch = $deleteUser->getUserDeleteBatch($users_to_delete);

  // Initialize the batch.
  batch_set($batch);

  // Start the batch process.
  drush_backend_batch_process();
}

/**
 * Drush callback to delete content.
 */
function drush_delete_all_delete_content() {
  // Initialize $content_type_options as FALSE to specify that all
  // content of all types should be deleted.
  // This will be overriden if user provides/choses a content type.
  $content_type_options = FALSE;

  $deleteContent = new ContentDeleteController();

  // Check for presence of '--type' in drush command.
  if (drush_get_option('type')) {
    // func_get_args() collects all keywords separated by space in an array.
    // To get the content types, we join all the keywords in a string and then
    // use 'comma' to separate them.
    $types = func_get_args();
    if ($types) {
      $content_types = implode(' ', $types);
      if (strpos($content_types, ',')) {
        $content_type_options = explode(',', $content_types);
      }
      else {
        $content_type_options = array($content_types);
      }
    }
    // Output all content types on screen and ask user to choose one.
    else {
      $content_type_options = [];
      $content_types = node_type_get_types();

      foreach ($content_types as $content_type_machine_name => $content_type) {
        $choices[$content_type_machine_name] = $content_type->label();
      }

      $content_type_options = drush_choice($choices, dt("Choose a content type to delete. All contents of this"));

      // Return if no role is chosen.
      if ($content_type_options === 0) {
        return;
      }
      $content_type_options = array($content_type_options);
    }
  }

  // Get users to delete.
  $nodes_to_delete = $deleteContent->getContentToDelete($content_type_options);

  // Get batch array.
  $batch = $deleteContent->getContentDeleteBatch($nodes_to_delete);

  $node = node_load(55);

  // Initialize the batch.
  batch_set($batch);

  // Start the batch process.
  drush_backend_batch_process();
}
