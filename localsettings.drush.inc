<?php

use machbarmacher\localsettings\Commands\CommandInterface;
use machbarmacher\localsettings\Commands\Commands;
use machbarmacher\localsettings\Compiler;
use machbarmacher\localsettings\CompilerFactory;
use machbarmacher\localsettings\Project;
use machbarmacher\localsettings\CompileMisc;

/**
 * Implements hook_drush_command().
 */
function localsettings_drush_command() {
  $items = array();

  $common = array(
    'outputformat' => array(
      'default' => 'key-value',
    ),
    'options' => [
      'force' => 'Overwrite existing files, defaults to 1.'
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
  );

  $items['localsettings-prepare'] = array(
      'description' => '1: Prepare ../localsettings/project.php.',
      'aliases' => array('lsp', 'ls1'),
      // @todo Check if drupals scaffolding conflicts with htaccess patch.
    ) + $common;

  $items['localsettings-compile'] = array(
      'description' => '2: Compile from project definition: sites, aliases, DBs, baseurls.',
      'arguments' => array(
        'include' => 'File to include. See example.project.php. Defaults to ../localsettings/project.php',
      ),
      'aliases' => array('lsc', 'ls2'),
      // For --simulate we don't need a drupal root.
      'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    ) + $common;

  $items['localsettings-scaffold'] = array(
      'description' => '3: Scaffold settings dispatcher, dev settings, Boxfile and .gitignore.',
      'arguments' => array(
        'include' => 'File to include. See example.mmm-project.php. Defaults to ../mmm-project.php',
      ),
      'aliases' => array('lss', 'ls3'),
      // For --simulate we don't need a drupal root.
      'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    ) + $common;

  $items['localsettings-activate'] = array(
      'description' => '4: Finally include settings dispatcher.',
      'arguments' => array(
        'site' => 'Site. Defaults to "default".',
      ),
      'aliases' => array('lsa', 'ls4'),
    ) + $common;

  $items['localsettings-pre-update'] = array(
      'description' => 'Helper: Before updating, restore htaccess from snippets folder.',
      'aliases' => array('lsbu'),
    ) + $common;

  $items['localsettings-post-update'] = array(
      'description' => 'Helper: After updating, move htaccess to snippets folder.',
      'aliases' => array('lspu'),
    ) + $common;

  $items['localsettings-post-clone'] = array(
      'description' => 'Helper: After cloning, symlink settings.local and htaccess.',
      'aliases' => array('lspc'),
    ) + $common;

  return $items;
}

function drush_localsettings_compile_validate($include = NULL) {
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $localsettings_compiler_factory = new CompilerFactory($include);
  return _localsettings_validate_root_or_simulate()
    && $localsettings_compiler_factory->validate()
    && _localsettings_autoload();
}


function drush_localsettings_compile($include = NULL) {
  $commands = new Commands();
  /** @var CompilerFactory $localsettings_compiler_factory */
  global $localsettings_compiler_factory;
  $compiler = $localsettings_compiler_factory->get();
  $compiler->compileAll($commands);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_scaffold_validate($include = NULL) {
  if (!$include) {
    $include = '../localsettings/project.php';
  }
  /** @var CompilerFactory $localsettings_compiler_factory */
  global $localsettings_compiler_factory;
  $localsettings_compiler_factory = new CompilerFactory($include);
  return _localsettings_validate_root_or_simulate()
    && $localsettings_compiler_factory->validate()
    && _localsettings_autoload();
}


function drush_localsettings_scaffold($installation_name = 'dev', $include = NULL) {
  $commands = new Commands();
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $compiler = $localsettings_compiler_factory->get();
  $compiler->scaffold($commands, $installation_name);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_prepare_validate() {
  return _localsettings_autoload();
}


function drush_localsettings_prepare() {
  $commands = new Commands();
  Compiler::prepare($commands);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_pre_update_validate() {
  return _localsettings_autoload();
}


function drush_localsettings_pre_update() {
  $commands = new Commands();
  Compiler::preUpdate($commands);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_post_update_validate($include = NULL) {
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $localsettings_compiler_factory = new CompilerFactory($include);
  return _localsettings_validate_root_or_simulate()
    && $localsettings_compiler_factory->validate()
    && _localsettings_autoload();
}


function drush_localsettings_post_update($include = NULL) {
  $commands = new Commands();
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $compiler = $localsettings_compiler_factory->get();
  $compiler->postUpdate($commands);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_post_clone_validate($include = NULL) {
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $localsettings_compiler_factory = new CompilerFactory($include);
  return _localsettings_validate_root_or_simulate()
    && $localsettings_compiler_factory->validate()
    && _localsettings_autoload();
}


function drush_localsettings_post_clone() {
  $commands = new Commands();
  global $localsettings_compiler_factory;
  /** @var CompilerFactory $localsettings_compiler_factory */
  $compiler = $localsettings_compiler_factory->get();
  $compiler->postClone($commands);
  return _localsettings_process_commands($commands);
}

function drush_localsettings_activate_validate($site = 'default') {
  return is_file("sites/$site/settings.php")
    && _localsettings_autoload();
}


function drush_localsettings_activate($site = 'default') {
  $commands = new Commands();
  Compiler::activateSite($commands, $site);
  return _localsettings_process_commands($commands);
}

/**
 * @return bool
 */
function _localsettings_validate_root_or_simulate() {
// Always try to bootstrap to prepare autoloader.
  $has_drupal_root = drush_bootstrap(DRUSH_BOOTSTRAP_DRUPAL_ROOT);
  if (!drush_get_context('DRUSH_SIMULATE')
    && !$has_drupal_root
  ) {
    return drush_set_error('DRUSH_localsettings_ERROR', dt('MMM builder needs to run in a valid drupal root if not called with --simulate.'));
  }
  return TRUE;
}

/**
 * @return bool
 */
function _localsettings_autoload() {
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
    return drush_set_error('DRUSH_localsettings_ERROR', dt('Autoloader not found.'));
  }
  return TRUE;
}

/**
 * @param CommandInterface $commands
 * @return array|bool
 */
function _localsettings_process_commands($commands) {
  $results = [];
  $commands->execute($results, drush_get_context('DRUSH_SIMULATE'));
  if (drush_get_context('DRUSH_SIMULATE') || drush_get_context('DRUSH_VERBOSE')) {
    return $results;
  }
}

