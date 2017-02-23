<?php

use clever_systems\mmm_builder\Commands\CommandInterface;
use clever_systems\mmm_builder\Compiler;
use clever_systems\mmm_builder\Project;
use clever_systems\mmm_builder\Scaffolder;

/**
 * Implements hook_drush_command().
 */
function mmm_builder_drush_command() {
  $items = array();

  $common = array(
    'outputformat' => array(
      'default' => 'key-value',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
  );

  $items['mmm-builder-prepare'] = array(
      // @fixme Add example project file.
      'description' => '1: Prepare settings dispatcher, dev settings, Boxfile and .gitignore.',
      'aliases' => array('mbp'),
    ) + $common;

  $items['mmm-builder-compile'] = array(
      'description' => '2: Compile from project definition: sites, aliases, DBs, baseurls.',
      'arguments' => array(
        'include' => 'File to include. See example.mmm-project.php. Defaults to ../mmm-project.php',
      ),
      'aliases' => array('mbc'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    ) + $common;

  $items['mmm-builder-post-clone'] = array(
      'description' => 'After cloning, symlink settings.local and htaccess.',
      'aliases' => array('mbp'),
    ) + $common;

  $items['mmm-builder-post-update'] = array(
      'description' => 'After updating, move htaccess to snippets folder.',
      'aliases' => array('mbp'),
    ) + $common;

  $items['mmm-builder-activate-site'] = array(
      'description' => 'Finally include settings dispatcher.',
      'arguments' => array(
        'site' => 'Site. Defaults to "default".',
      ),
      'aliases' => array('mbas'),
    ) + $common;

  return $items;
}

function drush_mmm_builder_compile_validate($include = '../mmm-project.php') {
  return _mmm_builder_validate_root_or_simulate()
    && _mmm_builder_validate_file($include)
    && _mmm_builder_autoload();
}


function drush_mmm_builder_compile($include = '../mmm-project.php') {
  /** @var Project $project */
  $project = include $include;
  $compiler = new Compiler($project);
  $commands = $compiler->compile();
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_prepare_validate() {
  return _mmm_builder_autoload();
}


function drush_mmm_builder_prepare() {
  $commands = (new Scaffolder())->doPrepare();
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_post_clone_validate() {
  return _mmm_builder_autoload();
}


function drush_mmm_builder_post_clone() {
  $commands = (new Scaffolder())->postClone();
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_post_update_validate() {
  return _mmm_builder_autoload();
}


function drush_mmm_builder_post_update() {
  $commands = (new Scaffolder())->postUpdate();
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_activate_site_validate($site = 'default') {
  return is_file("docroot/sites/$site/settings.php")
    && _mmm_builder_autoload();
}


function drush_mmm_builder_activate_site($site = 'default') {
  $commands = (new Scaffolder())->activateSite($site);
  return _mmm_builder_process_commands($commands);
}

/**
 * @return bool
 */
function _mmm_builder_validate_root_or_simulate() {
// Always try to bootstrap to prepare autoloader.
  $has_drupal_root = drush_bootstrap(DRUSH_BOOTSTRAP_DRUPAL_ROOT);
  if (!drush_get_context('DRUSH_SIMULATE')
    && !$has_drupal_root
  ) {
    return drush_set_error('DRUSH_mmm_builder_ERROR', dt('MMM builder needs to run in a valid drupal root if not called with --simulate.'));
  }
  return TRUE;
}

/**
 * @param $include
 * @return bool
 */
function _mmm_builder_validate_file($include) {
  if (!is_file($include)) {
    return drush_set_error('DRUSH_mmm_builder_ERROR', dt('File not found: @file', ['@file' => $include]));
  }
  return TRUE;
}

/**
 * @return bool
 */
function _mmm_builder_autoload() {
  // Hackily guess some autoloader paths.
  $paths = [
    DRUPAL_ROOT . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
  ];
  foreach ($paths as $path) {
    if (is_file($path)) {
      include $path;
    }
  }
  if (!class_exists(Compiler::class)) {
    return drush_set_error('DRUSH_mmm_builder_ERROR', dt('Autoloader not found.'));
  }
  return TRUE;
}

/**
 * @param CommandInterface $commands
 * @return bool
 */
function _mmm_builder_process_commands($commands) {
  $results = [];
  $commands->execute($results, drush_get_context('DRUSH_SIMULATE'));
  if (drush_get_context('DRUSH_SIMULATE') || drush_get_context('DRUSH_VERBOSE')) {
    return $results;
  }
}

