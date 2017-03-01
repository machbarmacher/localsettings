<?php


namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\DeleteFile;
use clever_systems\mmm_builder\Commands\MoveFile;
use clever_systems\mmm_builder\Commands\Symlink;
use clever_systems\mmm_builder\Commands\WriteFile;

class Scaffolder {
  // @fixme Fix composer scaffolding:
  //"pre-drupal-scaffold-cmd": [
  //"ddrush mbbu --force"
  //],
  //"post-drupal-scaffold-cmd": [
  //"ddrush mbpu --force"
  //]

  public static function moveAwayHtaccess(Commands $commands) {
    $commands->add(new MoveFile('.htaccess', '.htaccess.original'));
  }

  /**
   * @param \clever_systems\mmm_builder\Commands\Commands $commands
   */
  public static function moveBackHtaccess(Commands $commands) {
    // First delete, otherwise MoveFile anc composer will leave it as symlink
    // and only replace its content (which in fact changes the linked version).
    $commands->add(new DeleteFile('.htaccess'));
    $commands->add(new MoveFile('.htaccess.original', '.htaccess'));
  }

  public static function delegateSettings(Commands $commands, $site) {
    $commands->add(new WriteFile("sites/$site/settings.php", <<<EOD
<?php
require DRUPAL_ROOT . '/../settings.php';

EOD
    ));
  }

  public static function symlinkSettingsLocal(Commands $commands, $installation_name) {
    $commands->add(new Symlink('../settings.local.php', "settings.local.$installation_name.php"));
  }

  public static function symlinkHtaccess(Commands $commands, $installation_name) {
    $commands->add(new Symlink('.htaccess', ".htaccess.$installation_name"));
  }

  public static function writeSettings(Commands $commands, $drupal_major_version) {
    $settings_variable = ($drupal_major_version == 7) ? '$conf' : '$settings';
    $commands->add(new WriteFile('../settings.php', <<<EOD
<?php
// MMM settings file.
require '../vendor/autoload.php';
use clever_systems\mmm_runtime\Runtime;

require '../settings.baseurl.php';
require '../settings.databases.php';
Runtime::getEnvironment()->settings($settings_variable, \$databases);
include '../settings.common.php';
include '../settings.local.php';

EOD
    ));
  }

  public static function writeBoxfile(Commands $commands) {
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
  }

  public static function writeComposerGitignore(Commands $commands) {
    $commands->add(new WriteFile('../.gitignore', <<<EOD
# Ignore paths that are symlinked per environment.
/settings.local.php
/docroot/.htaccess
# Ignore server content.
/config
/tmp
/logs
# Ignore application content.
/private
/docroot/sites/*/files
/docroot/sites/*/private

EOD
    ));
  }

  public static function wrieDrupalGitignore(Commands $commands) {
    $commands->add(new WriteFile('.gitignore', ''));
  }

  /**
   * @param $commands
   */
  public static function writeProject(Commands $commands) {
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
  ->addSite('http://test.example.com');

// Do not forget!
return $project;

EOD
    ));
  }
}
