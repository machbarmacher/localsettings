<?php
/**
 * @file Compiler.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\Commands\AlterFile;
use machbarmacher\localsettings\Commands\Commands;
use machbarmacher\localsettings\Commands\EnsureDirectory;
use machbarmacher\localsettings\Commands\Symlink;
use machbarmacher\localsettings\Commands\WriteFile;
use machbarmacher\localsettings\RenderPhp\PhpAssignment;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\RenderPhp\PhpIf;
use machbarmacher\localsettings\RenderPhp\PhpRawStatement;
use machbarmacher\localsettings\RenderPhp\PhpStatements;
use machbarmacher\localsettings\Tools\IncludeTool;

class Compiler {
  /** @var Project */
  protected $project;

  protected function getCurrentInstallationName() {
    foreach ($this->project->getInstallations() as $installation) {
      if ($installation->isCurrent()) {
        return $installation->getName();
      }
    }
    return NULL;
  }

  /**
   * Compiler constructor.
   * @param Project $project
   */
  public function __construct(Project $project) {
    $this->project = $project;
  }

  /**
   * @return \machbarmacher\localsettings\Project
   */
  public function getProject() {
    return $this->project;
  }

  public function compileAll(Commands $commands) {
    $php = new PhpFile();
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      if ($installation->isMultisite()) {
        $installation->compileSitesPhp($php);
      }
    }
    // Only write if we have a multisite.
    if (!$php->isEmpty()) {
      $commands->add(new WriteFile('../localsettings/sites.php', $php));
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    $php = new PhpFile();
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $installation->compileAliases($php);
    }
    CompileAliases::addAliasAlterCode($php, $this->project->getInstallations());
    $commands->add(new WriteFile("../localsettings/aliases.drushrc.php", $php));

    $server_setting_files = [];
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $canonical_installation_name = $installation->getName();
      $php = new PhpFile();
      $php->addRawStatement('// Basic installation facts.');
      CompileSettings::addInstallationFacts($php, $this->project, $installation);
      $php->addRawStatement('');
      $php->addRawStatement('// Base URLs');
      $installation->compileBaseUrls($php);
      $php->addRawStatement('');
      $php->addRawStatement('// DB Credentials');
      $installation->compileDbCredentials($php);
      $php->addRawStatement('');
      $php->addRawStatement('// Installation specific');
      $installation->getServer()->addInstallationSpecificSettings($php, $installation);
      $php->addRawStatement('');
      $php->addRawStatement('// Server specific');
      $server = $installation->getServer();
      $server_name = $server->getTypeName();
      if (!isset($server_setting_files[$server_name])) {
        $server_php = new PhpFile();
        $server->addServerSpecificSettings($server_php, $this->project);
        $server_setting_file = "settings.server.$server_name.php";
        $server_setting_files[$server_name] = $server_setting_file;
        $commands->add(new WriteFile("../localsettings/$server_setting_file", $server_php));
      }
      $php->addRawStatement("include '../localsettings/{$server_setting_files[$server_name]}';");
      $commands->add(new WriteFile("../localsettings/settings.generated.{$canonical_installation_name}.php", $php));
    }

    $php = new PhpFile();
    CompileSettings::addBasicFacts($php, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated-basic.php", $php));

    $php = new PhpFile();
    CompileSettings::addGenericSettings($php, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated-common.php", $php));
  }

  public static function prepare(Commands $commands) {
    // Step 1
    CompileMisc::writeProject($commands);
    // Step 2 is compiling
  }

  public function scaffold(Commands $commands, $current_installation_name) {
    // Step 3
    $drupal_major_version = $this->getProject()->getDrupalMajorVersion();
    $installations = $this->project->getInstallations();
    $current_installation = $installations[$current_installation_name];

    foreach ($installations as $installation_name => $_) {
      $commands->add(new EnsureDirectory("../localsettings/crontab.d/$installation_name"));
    }
    $commands->add(new EnsureDirectory("../localsettings/crontab.d/common"));
    $commands->add(new WriteFile("../localsettings/crontab.d/common/50-cron",
      "0 * * * * drush -r \$DRUPAL_ROOT cron -y\n"));

    if (!file_exists('../docroot') && is_dir('../web')) {
      $commands->add(new Symlink('docroot', 'web'));
    }

    if ($drupal_major_version != 7) {
      $commands->add(new WriteFile('../config-sync/.gitkeep', ''));
    }

    // Write settings.custom-common.php
    // This is not idempotent, and will break due to recursion if done twice.
    if (!file_exists('../localsettings/settings.custom-common.php')) {
      $php = new PhpFile();
      // Transfer hash salt.
      foreach ($current_installation->getSiteUris() as $site => $_) {
        $settings = IncludeTool::getVariables("sites/$site/settings.php");
        if (!empty($settings['drupal_hash_salt'])) {
          $add_hash_salt = new PhpAssignment('$drupal_hash_salt', $settings['drupal_hash_salt']);
          if ($current_installation->isMultisite()) {
            $add_hash_salt = new PhpIf('', $add_hash_salt);
          }
          $php->addStatement($add_hash_salt);
        }
      }
      $commands->add(new WriteFile('../localsettings/settings.custom-common.php', $php));
    }

    // Write aliases.drushrc.php alias
    $commands->add(new Symlink('../drush/aliases.drushrc.php', '../localsettings/aliases.drushrc.php'));

    // Write settings.custom.*.php
    foreach ($installations as $installation_name => $_) {
      $commands->add(new WriteFile("../localsettings/settings.custom.$installation_name.php", new PhpFile()));
    }

    CompileMisc::writeSettings($commands, $drupal_major_version);

    // @todo Delegate to server.
    CompileMisc::writeBoxfile($commands);

    CompileMisc::writeGitignoreForComposer($commands);

    $this->postClone($commands);
    $this->postUpdate($commands);
  }

  public static function preUpdate(Commands $commands) {
    // Prepare update and scaffold of docroot.
    // Make htacces a file again, not a symlink.
    CompileMisc::moveBackHtaccess($commands);
  }

  public function postUpdate(Commands $commands) {
    // A docroot update brought upstream versions:
    // Use our gitignore; Alter and symlink htaccess.
    CompileMisc::writeGitignoreForDrupal($commands);
    if (file_exists('.htaccess') && !is_link('.htaccess')) {
      CompileMisc::moveAwayHtaccess($commands);
      CompileMisc::letInstallationsAlterHtaccess($commands, $this->project);
      CompileMisc::symlinkHtaccess($commands, $this->getCurrentInstallationName());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    CompileMisc::symlinkSettingsLocal($commands, $this->getCurrentInstallationName());
    CompileMisc::symlinkHtaccess($commands, $this->getCurrentInstallationName());

    return $commands;
  }

  public static function activateSite(Commands $commands, $site) {
    // Step 4
    CompileMisc::delegateSettings($commands, $site);
  }

}
