<?php


namespace machbarmacher\localsettings;


use machbarmacher\localsettings\Commands\AlterFile;
use machbarmacher\localsettings\Commands\Commands;
use machbarmacher\localsettings\Commands\DeleteFile;
use machbarmacher\localsettings\Commands\MoveFile;
use machbarmacher\localsettings\Commands\Symlink;
use machbarmacher\localsettings\Commands\WriteFile;
use Symfony\Component\Yaml\Yaml;

class CompileMisc {

  public static function letDeclarationsAlterHtaccess(Commands $commands, Project $project) {
    $original_file = !drush_get_option('simulate')
      // Not simulated? Look at the correct location.
      ? '.htaccess.original'
      // Simulated ? Look at the previous location.
      : '.htaccess';
    foreach ($project->getDeclarations() as $declaration) {
      $environment_name = $declaration->getEnvironmentName();
      $commands->add(new AlterFile($original_file, ".htaccess.environment.$environment_name",
        function ($content) use ($declaration) {
          return $declaration->alterHtaccess($content);
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
return; // Ignore suffix code e.g. by wodby.

EOD
    ));
  }

  public static function symlinkSettingsGeneratedPerDeclaration(Commands $commands, $declaration_name) {
    $commands->add(new Symlink('../localsettings/settings.generated.declaration.THIS.php', "settings.generated.declaration.$declaration_name.php"));
  }

  public static function symlinkSettingsCustomPerEnvironment(Commands $commands, $environment_name) {
    $commands->add(new Symlink('../localsettings/settings.custom.environment.THIS.php', "settings.custom.environment.$environment_name.php"));
  }

  public static function symlinkHtaccessPerEnvironment(Commands $commands, $environment_name) {
    $commands->add(new Symlink('.htaccess', ".htaccess.environment.$environment_name"));
  }

  public static function writeSettings(Commands $commands, $drupal_major_version) {
    $commands->add(new WriteFile('../localsettings/settings.php', <<<EOD
<?php
require '../localsettings/settings.generated.initial.php';
require '../localsettings/settings.generated.declaration.THIS.php';
require '../localsettings/settings.generated.additional.php';
require '../localsettings/settings.custom.initial.php';
require '../localsettings/settings.custom.environment.THIS.php';
require '../localsettings/settings.custom.additional.php';

EOD
    ));
  }

  public static function writeBoxfile(Commands $commands, Project $project) {
    // @fixme Iterate installations
    // @fixme Delegate to fsb server. Also the docroot->web symlink.
    $shared_folders = ['logs'];
    foreach ($project->getCurrentDeclaration()->getSiteUris() as $site => $_) {
      $shared_folders[] = "docroot/sites/$site/files";
    }
    $env_specific_files = [];
    foreach ($project->getDeclarations() as $declaration) {
      // Generated settings go by declaration.
      $declaration_name = $declaration->getDeclarationName();
      $env_specific_files['localsettings/settings.generated.declaration.THIS.php']
        [$declaration_name] = "settings.generated.declaration.$declaration_name.php";
      // Custom settings and htaccess go by environment.
      $environment_name = $declaration->getEnvironmentName();
      $env_specific_files['docroot/.htaccess'][$declaration_name] = ".htaccess.environment.$environment_name";
      $env_specific_files['localsettings/settings.custom.environment.THIS.php']
        [$declaration_name] = "settings.custom.environment.$environment_name.php";
    }

    $boxfile = [];
    $boxfile['version'] = 2; // No string! FSB is sooo picky here.
    $boxfile['shared_folders'] = $shared_folders;
    $boxfile['env_specific_files'] = $env_specific_files;

    $commands->add(new WriteFile('../Boxfile', Yaml::dump($boxfile, 9)));
  }

  public static function writeGitignoreForComposer(Commands $commands) {
    $commands->add(new WriteFile('../.gitignore', <<<EOD
.git/
# Ignore paths that are symlinked per environment.
/localsettings/settings.generated.declaration.THIS.php
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

  public static function writeGitignoreForDrupalRoot(Commands $commands) {
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

namespace machbarmacher\localsettings;
use machbarmacher\localsettings\ServerType\FreistilboxServer;
use machbarmacher\localsettings\ServerType\UberspaceServer;
use machbarmacher\localsettings\ServerType\WodbyServer;

$project = new Project(7\8);

// Add all installations that match a docroot pattern.
$project->globInstallations('dev', new UberspaceServer('HOST', 'USER'))
  ->addSite('httlobp://dev.USER.HOST.uberspace.de')
  ->setDocroot('/var/www/virtual/USER/installations/{{installation}}/docroot')
  ->setDbCredentialPattern('USER_{{installation}}_{{site}}');

$project->addInstallation('live', new FreistilboxServer('c145', 'sXXXX'))
  ->addSite('http://example.com');

// Note that all installations test-foo have environment test.
$project->addInstallation('test-1', new FreistilboxServer('c145', 'sXXXX'))
  ->addSite('http://test.example.com');
$project->addInstallation('test-2', new FreistilboxServer('c145', 'sXXXX'))
  ->addSite('http://test2.example.com');

return $project;

EOD
    ));
  }
}
