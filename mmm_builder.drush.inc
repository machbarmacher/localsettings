<?php

use clever_systems\mmm_builder\Compiler;
use clever_systems\mmm_builder\Project;

/**
 * Implements hook_drush_command().
 */
function mmm_builder_drush_command() {
  $items = array();

  $items['mmm-builder'] = array(
    'description' => "Drush command mmm-builder.",
    'arguments' => array(
      'include' => 'File to include. See example.mmm-project.php. Defaults to ../mmm-project.php',
    ),
    // Give one or more example commandline usages for this command
    'examples' => array(
      'drush mmm-builder example.project.php --simulate' => 'Do something.',
    ),
    // Delete the 'outputformat' record if command does not
    // produce any output.
    'outputformat' => array(
      'default' => 'key-value',
    ),
    'aliases' => array('mmmb'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  return $items;
}

/**
 * Implementation of drush_hook_COMMAND().
 */
function drush_mmm_builder($include = '../mmm-project.php') {
  /** @var Project $project */
  $project = include $include;
  $compiler = new Compiler($project);
  $files = $compiler->compile();
  if (!drush_get_context('DRUSH_SIMULATE')) {
    foreach ($files as $name => $file) {
      drush_mkdir(dirname($files));
      $bytes = file_put_contents($name, $file);
      if ($bytes === FALSE) {
        return drush_set_error('DRUSH_mmm_builder_ERROR', dt('MMM builder could not write file: @file', ['@file' => $name]));
      }
    }
  }
  if (drush_get_context('DRUSH_SIMULATE') || drush_get_context('DRUSH_VERBOSE')) {
    return $files;
  }
}

/**
 * Implementation of drush_hook_COMMAND_validate().
 */
function drush_mmm_builder_validate($include = '../mmm-project.php') {
  // Always try to bootstrap to prepare autoloader.
  $has_drupal_root = drush_bootstrap(DRUSH_BOOTSTRAP_DRUPAL_ROOT);
  if (!drush_get_context('DRUSH_SIMULATE')
    && !$has_drupal_root
  ) {
    return drush_set_error('DRUSH_mmm_builder_ERROR', dt('MMM builder needs to run in a valid drupal root if not called with --simulate.'));
  }
  if (!is_file($include)) {
    return drush_set_error('DRUSH_mmm_builder_ERROR', dt('File not found: @file', ['@file' => $include]));
  }

  // Hackily guess some autoloader paths.
  $paths = [
    __DIR__ . '/vendor/autoload.php',
    DRUPAL_ROOT . '/../vendor/autoload.php'
  ];
  foreach ($paths as $path) {
    if (is_file($path)) {
      include $path;
    }
  }

  return TRUE;
}
