<?php

use clever_systems\mmm_builder\Commands\CommandInterface;
use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Compiler;
use clever_systems\mmm_builder\CompilerFactory;
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
    'options' => [
      'force' => 'Overwrite existing  files.'
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
  );

  $items['mmm-builder-prepare'] = array(
      'description' => '1: Prepare mmm-project.php.',
      'aliases' => array('mbp', 'mb1'),
      // @todo Check if drupals scaffolding conflicts with htaccess patch.
    ) + $common;

  $items['mmm-builder-compile'] = array(
      'description' => '2: Compile from project definition: sites, aliases, DBs, baseurls.',
      'arguments' => array(
        'include' => 'File to include. See example.mmm-project.php. Defaults to ../mmm-project.php',
      ),
      'aliases' => array('mbc', 'mb2'),
      // For --simulate we don't need a drupal root.
      'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    ) + $common;

  $items['mmm-builder-scaffold'] = array(
      'description' => '3: Scaffold settings dispatcher, dev settings, Boxfile and .gitignore.',
      'arguments' => array(
        'include' => 'File to include. See example.mmm-project.php. Defaults to ../mmm-project.php',
      ),
      'aliases' => array('mbs', 'mb3'),
      // For --simulate we don't need a drupal root.
      'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    ) + $common;

  $items['mmm-builder-activate'] = array(
      'description' => '4: Finally include settings dispatcher.',
      'arguments' => array(
        'site' => 'Site. Defaults to "default".',
      ),
      'aliases' => array('mba', 'mb4'),
    ) + $common;

  $items['mmm-builder-pre-update'] = array(
      'description' => 'Helper: Before updating, restore htaccess from snippets folder.',
      'aliases' => array('mbbu'),
    ) + $common;

  $items['mmm-builder-post-update'] = array(
      'description' => 'Helper: After updating, move htaccess to snippets folder.',
      'aliases' => array('mbpu'),
    ) + $common;

  $items['mmm-builder-post-clone'] = array(
      'description' => 'Helper: After cloning, symlink settings.local and htaccess.',
      'aliases' => array('mbpc'),
    ) + $common;

  return $items;
}

function drush_mmm_builder_compile_validate($include = NULL) {
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $mmm_builder_compiler_factory = new CompilerFactory($include);
  return _mmm_builder_validate_root_or_simulate()
    && $mmm_builder_compiler_factory->validate()
    && _mmm_builder_autoload();
}


function drush_mmm_builder_compile($include = NULL) {
  $commands = new Commands();
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  global $mmm_builder_compiler_factory;
  $compiler = $mmm_builder_compiler_factory->get();
  $compiler->compile($commands);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_scaffold_validate($include = NULL) {
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  global $mmm_builder_compiler_factory;
  $mmm_builder_compiler_factory = new CompilerFactory($include);
  return _mmm_builder_validate_root_or_simulate()
    && $mmm_builder_compiler_factory->validate()
    && _mmm_builder_autoload();
}


function drush_mmm_builder_scaffold($installation_name = 'dev', $include = NULL) {
  $commands = new Commands();
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $compiler = $mmm_builder_compiler_factory->get();
  $compiler->scaffold($commands, $installation_name);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_prepare_validate() {
  return _mmm_builder_autoload();
}


function drush_mmm_builder_prepare() {
  $commands = new Commands();
  Compiler::prepare($commands);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_pre_update_validate() {
  return _mmm_builder_autoload();
}


function drush_mmm_builder_pre_update() {
  $commands = new Commands();
  Compiler::preUpdate($commands);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_post_update_validate($include = NULL) {
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $mmm_builder_compiler_factory = new CompilerFactory($include);
  return _mmm_builder_validate_root_or_simulate()
    && $mmm_builder_compiler_factory->validate()
    && _mmm_builder_autoload();
}


function drush_mmm_builder_post_update($include = NULL) {
  $commands = new Commands();
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $compiler = $mmm_builder_compiler_factory->get();
  $compiler->postUpdate($commands);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_post_clone_validate($include = NULL) {
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $mmm_builder_compiler_factory = new CompilerFactory($include);
  return _mmm_builder_validate_root_or_simulate()
    && $mmm_builder_compiler_factory->validate()
    && _mmm_builder_autoload();
}


function drush_mmm_builder_post_clone() {
  $commands = new Commands();
  global $mmm_builder_compiler_factory;
  /** @var CompilerFactory $mmm_builder_compiler_factory */
  $compiler = $mmm_builder_compiler_factory->get();
  $compiler->postClone($commands);
  return _mmm_builder_process_commands($commands);
}

function drush_mmm_builder_activate_validate($site = 'default') {
  return is_file("sites/$site/settings.php")
    && _mmm_builder_autoload();
}


function drush_mmm_builder_activate($site = 'default') {
  $commands = new Commands();
  Compiler::activateSite($commands, $site);
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
 * @return array|bool
 */
function _mmm_builder_process_commands($commands) {
  $results = [];
  $commands->execute($results, drush_get_context('DRUSH_SIMULATE'));
  if (drush_get_context('DRUSH_SIMULATE') || drush_get_context('DRUSH_VERBOSE')) {
    return $results;
  }
}

