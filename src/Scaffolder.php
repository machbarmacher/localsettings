<?php


namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\EnsureDirectory;
use clever_systems\mmm_builder\Commands\MoveFile;
use clever_systems\mmm_builder\Commands\Symlink;
use clever_systems\mmm_builder\Commands\WriteFile;

class Scaffolder {
  /**
   * Get installation name - for now just assume dev.
   */
  function getInstallationName() {
    return 'dev';
  }

  function doPrepare() {
    $installation_name = $this->getInstallationName();
    $commands = new Commands();
    $compiler = (new CompilerFactory())->get();
    $environment_names = $compiler->getEnvironmentNames();

    foreach ($environment_names as $environment_name) {
      $commands->add(new EnsureDirectory("../crontab.$environment_name.d"));
    }
    $commands->add(new EnsureDirectory("../crontab.common.d"));

    if (!file_exists('../docroot') && is_dir('../web')) {
      $commands->add(new Symlink('docroot', 'web'));
    }

    $commands->add(new WriteFile('../settings.common.php', "<?php\n"));

    $compiler->writeSettingsLocal($commands, $installation_name);

    $commands->add(new WriteFile('../settings.php', <<<EOD
<?php
// MMM settings file.
require '../vendor/autoload.php';
use clever_systems\mmm_runtime\Runtime;

require '../settings.baseurl.php';
require '../settings.databases.php';
Runtime::getEnvironment()->settings();
include '../settings.common.php';
include '../settings.local.php';

EOD
      ));

    $commands->add(new WriteFile('../Boxfile', <<<EOD
version: 2.0
shared_folders:
  - docroot/sites/default/files
  - logs
env_specific_files:
  docroot/.htaccess:
    production: .htaccess.live
  settings.local.php:
    production: settings.local.live.php

EOD
      ));

    $commands->add(new WriteFile('../.gitignore', <<<EOD
# Ignore paths that are symlinked per environment.
/settings.local.php
/docroot/.htaccess

EOD
    ));

    $commands->add(new WriteFile('.gitignore', <<<EOD
# Ignore paths that contain user-generated content.
/sites/*/files
/sites/*/private

EOD
    ));

    $commands->add(new WriteFile('../mmm-project.php', <<<'EOD'
<?php
/**
 * @file mmm-project.php
 */
namespace clever_systems\mmm_builder;
use clever_systems\mmm_builder\ServerType\FreistilboxServer;
use clever_systems\mmm_builder\ServerType\UberspaceServer;

// TODO: After adjusting, run "drusn mbc", when ok "drush mba".

$project = new Project(8);

$project->addInstallation('dev', new UberspaceServer('HOST', 'USER'))
  ->addSite('http://dev.USER.HOST.uberspace.de')
  ->setDocroot('/var/www/virtual/USER/installations/dev/docroot')
  ->setDbCredentialPattern('USER_{{installation}}_{{site}}');

$project->addInstallation('live', new FreistilboxServer('c145', 's2222'))
  ->addSite('http://example.com');

$project->addInstallation('test', new FreistilboxServer('c145', 's2323'))
  ->addSite('http://test.example.com')
  ->useEnvironment('live');

// Do not forget!
return $project;

EOD
    ));

    // Save htaccess to .original.
    $this->postUpdate($commands);

    // Let installations alter their .htaccess.
    $compiler->alterHtaccess($commands);

    // Symlink installation-specific htaccess and settings.local
    $this->postClone($commands);

    return $commands;
  }

  function postClone($commands = NULL) {
    $installation_name = $this->getInstallationName();
    if (!$commands) {
      $commands = new Commands();
    }

    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    // Symlink environment specific files.
    // Note that target is relative to source directory.
    $link_targets = [
      ['../settings.local.php', $target = "settings.local.$installation_name.php"],
      ['.htaccess', $target = ".htaccess.$installation_name"],
    ];
    foreach ($link_targets as list($link, $target)) {
      $commands->add(new Symlink($link, $target));
    }
    return $commands;
  }

  function preUpdate($commands = NULL) {
    if (!$commands) {
      $commands = new Commands();
    }

    $commands->add(new MoveFile('.htaccess.original', '.htaccess'));

    return $commands;
  }

  function postUpdate($commands = NULL) {
    if (!$commands) {
      $commands = new Commands();
    }

    $commands->add(new MoveFile('.htaccess', '.htaccess.original'));

    return $commands;
  }

  function activateSite($site) {
    $commands = new Commands();

    $commands->add(new WriteFile("sites/$site/settings.php", <<<EOD
<?php
require DRUPAL_ROOT . '../settings.php';

EOD
      ));

    return $commands;
  }
}
