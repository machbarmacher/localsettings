<?php


namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\WriteString;

class Scaffolder {
  /**
   * Get installation name - for now just assume dev.
   */
  function getInstallationName() {
    return 'dev';
  }

  function doPrepare() {
    // @fixme add symlinks for docroot->web
    $installation_name = $this->getInstallationName();

    $commands = new Commands();

    $commands->add(new WriteString("../settings.local.$installation_name.php",
      file_get_contents('docroot/sites/default/settings.php')));

    $commands->add(new WriteString('../settings.php', <<<EOD
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

    $commands->add(new WriteString('Boxfile', <<<EOD
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

    $commands->add(new WriteString('.gitignore', <<<EOD
# Ignore paths that are symlinked per environment.
/settings.local.php
/docroot/.htaccess

EOD
    ));

    $commands->add(new WriteString('docroot/.gitignore', <<<EOD
# Ignore paths that contain user-generated content.
/sites/*/files
/sites/*/private

EOD
    ));

    return $commands;
  }

  function postClone() {
    $installation_name = $this->getInstallationName();
    // @fixme Symlink settings.local.php & docroot/.htaccess
  }

  function postUpdate() {
    $installation_name = $this->getInstallationName();
    // @fixme move docroot/.htaccess to docroot/.htaccess.all.d/50-core
    $this->postClone();
  }
}
