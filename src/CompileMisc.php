<?php


namespace machbarmacher\localsettings;


use machbarmacher\localsettings\Commands\AlterFile;
use machbarmacher\localsettings\Commands\Commands;
use machbarmacher\localsettings\Commands\DeleteFile;
use machbarmacher\localsettings\Commands\MoveFile;
use machbarmacher\localsettings\Commands\Symlink;
use machbarmacher\localsettings\Commands\WriteFile;

class CompileMisc {

  public static function letEnvironmentsAlterHtaccess(Commands $commands, Project $project) {
    $original_file = !drush_get_option('simulate')
      // Not simulated? Look at the correct location.
      ? '.htaccess.original'
      // Simulated ? Look at the previous location.
      : '.htaccess';
    foreach ($project->getEnvironments() as $environment) {
      $environment_name = $environment->getName();
      $commands->add(new AlterFile($original_file, ".htaccess.$environment_name",
        function ($content) use ($environment) {
          return $environment->alterHtaccess($content);
        }));
    }
  }

  // @fixme Fix composer scaffolding:
  //"pre-drupal-scaffold-cmd": [
  //"ddrush mbbu --force"
  //],
  //"post-drupal-scaffold-cmd": [
  //"ddrush mbpu --force"
  //]
  // @fixme Add post composer update hook "delete .git dirs"

  public static function moveAwayHtaccess(Commands $commands) {
    $commands->add(new MoveFile('.htaccess', '.htaccess.original'));
  }

  /**
   * @param \machbarmacher\localsettings\Commands\Commands $commands
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
require DRUPAL_ROOT . '/../localsettings/settings.php';

EOD
    ));
  }

  public static function symlinkSettingsLocal(Commands $commands, $environment_name) {
    $commands->add(new Symlink('../localsettings/settings.custom.environment.THIS.php', "settings.custom.environment.$environment_name.php"));
    $commands->add(new Symlink('../localsettings/settings.generated.environment.THIS.php', "settings.generated.environment.$environment_name.php"));
  }

  public static function symlinkHtaccess(Commands $commands, $environment_name) {
    $commands->add(new Symlink('.htaccess', ".htaccess.$environment_name"));
  }

  public static function writeSettings(Commands $commands, $drupal_major_version) {
    $commands->add(new WriteFile('../localsettings/settings.php', <<<EOD
<?php
require '../localsettings/settings.generated.initial.php';
require '../localsettings/settings.generated.environment.THIS.php';
require '../localsettings/settings.generated.additional.php';
include '../localsettings/settings.custom.initial.php';
include '../localsettings/settings.custom.environment.THIS.php';
include '../localsettings/settings.custom.additional.php';

EOD
    ));
  }

  public static function writeBoxfile(Commands $commands) {
    // @fixme Iterate installations
    // @fixme Delegate to fsb server. Also the docroot->web symlink.
    $commands->add(new WriteFile('../Boxfile', <<<EOD
version: 2.0
shared_folders:
  - docroot/sites/default/files
  - logs
env_specific_files:
  docroot/.htaccess:
    live: .htaccess.live
    test: .htaccess.test
  localsettings/settings.generated.environment.THIS.php:
    live: settings.generated.environment.live.php
    test: settings.generated.environment.test.php

EOD
      // @fixme Allow more than live and test.
    ));
  }

  public static function writeGitignoreForComposer(Commands $commands) {
    $commands->add(new WriteFile('../.gitignore', <<<EOD
.git
# Ignore paths that are symlinked per environment.
/localsettings/settings.generated.environment.THIS.php
/localsettings/settings.custom.environment.THIS.php
# Ignore server content.
/config
/tmp
/logs
# Ignore application content.
/private

EOD
    ));
  }

  public static function writeGitignoreForDrupal(Commands $commands) {
    $commands->add(new WriteFile('.gitignore', <<<EOD
# Ignore application content.
/sites/*/files
/sites/*/private
# Ignore server specific files.
/.htaccess

EOD
      ));
  }

  /**
   * @param $commands
   */
  public static function writeProject(Commands $commands) {
    $commands->add(new WriteFile('../localsettings/project.php', <<<'EOD'
<?php
/**
 * @file localsettings/project.php
 */
namespace machbarmacher\localsettings;
use machbarmacher\localsettings\ServerType\FreistilboxServer;
use machbarmacher\localsettings\ServerType\UberspaceServer;

// TODO: Adjust, then run "drush ls2/3/4".

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
