<?php


namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\DeleteFile;
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

  // @fixme Do not double-massage .htaccess
  function doPrepare() {
    $installation_name = $this->getInstallationName();
    $commands = new Commands();
    $compiler = (new CompilerFactory())->get();
    $environment_names = $compiler->getEnvironmentNames();

    foreach ($environment_names as $environment_name) {
      $commands->add(new EnsureDirectory("../crontab.d/$environment_name"));
    }
    $commands->add(new EnsureDirectory("../crontab.d/common"));
    $commands->add(new WriteFile("../crontab.d/common/50-cron",
      "0 * * * * drush -r \$DRUPAL_ROOT cron -y\n"));

    if (!file_exists('../docroot') && is_dir('../web')) {
      $commands->add(new Symlink('docroot', 'web'));
    }

    $commands->add(new WriteFile('../settings.common.php', "<?php\n"));

    $compiler->writeSettingsLocal($commands, $installation_name);

    $drupal_major_version = $compiler->getProject()->getDrupalMajorVersion();
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

    $commands->add(new WriteFile('.gitignore', ''));

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
  ->behaveLike('live');

// Do not forget!
return $project;

EOD
    ));

    // Save htaccess to .original.
    $this->postUpdate($commands);

    return $commands;
  }

  function postClone($commands = NULL) {
    if (!$commands) {
      $commands = new Commands();
    }

    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    $this->symlinkEnvironmentSpecificFiles($commands);

    return $commands;
  }

  function preUpdate($commands = NULL) {
    if (!$commands) {
      $commands = new Commands();
    }

    // First delete, otherwise MoveFile anc composer will leave it as symlink
    // and only replace its content (which in fact changes the linked version).
    $commands->add(new DeleteFile('.htaccess'));
    $commands->add(new MoveFile('.htaccess.original', '.htaccess'));

    return $commands;
  }

  function postUpdate($commands = NULL) {
    if (!$commands) {
      $commands = new Commands();
    }
    $compiler = (new CompilerFactory())->get();

    $commands->add(new WriteFile('.gitignore', ''));

    if (file_exists('.htaccess') && !is_link('.htaccess')) {
      $commands->add(new MoveFile('.htaccess', '.htaccess.original'));

      // Let installations alter their .htaccess.
      $compiler->alterHtaccess($commands);

      $this->symlinkEnvironmentSpecificFiles($commands);
    }

    return $commands;
  }

  function activateSite($site) {
    $commands = new Commands();

    $commands->add(new WriteFile("sites/$site/settings.php", <<<EOD
<?php
require DRUPAL_ROOT . '/../settings.php';

EOD
      ));

    return $commands;
  }

  public function symlinkEnvironmentSpecificFiles(Commands $commands) {
// Symlink environment specific files.
    // Note that target is relative to source directory.
    $installation_name = $this->getInstallationName();
    $link_targets = [
      [
        '../settings.local.php',
        $target = "settings.local.$installation_name.php"
      ],
      ['.htaccess', $target = ".htaccess.$installation_name"],
    ];
    foreach ($link_targets as list($link, $target)) {
      $commands->add(new Symlink($link, $target));
    }
  }
}
