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

  protected function getCurrentEnvironmentName() {
    foreach ($this->project->getEnvironments() as $environment) {
      if ($environment->isCurrent()) {
        return $environment->getName();
      }
    }
    return 'NONE';
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
    foreach ($this->project->getEnvironments() as $environment_name => $environment) {
      if ($environment->isMultisite()) {
        $environment->compileSitesPhp($php);
      }
    }
    // Only write if we have a multisite.
    if (!$php->isEmpty()) {
      $commands->add(new WriteFile('../localsettings/sites.php', $php));
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    $php = new PhpFile();
    foreach ($this->project->getEnvironments() as $environment_name => $environment) {
      $environment->compileAliases($php);
    }
    CompileAliases::addAliasAlterCode($php, $this->project->getEnvironments());
    $commands->add(new WriteFile("../localsettings/aliases.drushrc.php", $php));

    $server_setting_files = [];
    foreach ($this->project->getEnvironments() as $environment_name => $environment) {
      $environment_name = $environment->getName();
      $php = new PhpFile();
      $php->addRawStatement('// Environment info.');
      $environment->compileEnvironmentInfo($php);
      $php->addRawStatement('');
      $php->addRawStatement('// Base URLs');
      $environment->compileBaseUrls($php);
      $php->addRawStatement('');
      $php->addRawStatement('// DB Credentials');
      $environment->compileDbCredentials($php);
      $php->addRawStatement('');
      $php->addRawStatement('// Environment specific');
      $environment->getServer()->addEnvironmentSpecificSettings($php, $environment);
      $php->addRawStatement('');
      $php->addRawStatement('// Server specific');
      $server = $environment->getServer();
      $server_name = $server->getTypeName();
      if (!isset($server_setting_files[$server_name])) {
        $server_php = new PhpFile();
        $server->addServerSpecificSettings($server_php, $this->project);
        $server_setting_file = "settings.generated.server.$server_name.php";
        $server_setting_files[$server_name] = $server_setting_file;
        $commands->add(new WriteFile("../localsettings/$server_setting_file", $server_php));
      }
      $php->addRawStatement("include '../localsettings/{$server_setting_files[$server_name]}';");
      $commands->add(new WriteFile("../localsettings/settings.generated.environment.{$environment_name}.php", $php));
    }

    $php = new PhpFile();
    CompileSettings::addBasicFacts($php, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated.initial.php", $php));

    $php = new PhpFile();
    CompileSettings::addGenericSettings($php, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated.additional.php", $php));
  }

  public static function prepare(Commands $commands) {
    // Step 1
    CompileMisc::writeProject($commands);
    // Step 2 is compiling
  }

  public function scaffold(Commands $commands, $current_environment_name) {
    // Step 3
    $drupal_major_version = $this->getProject()->getDrupalMajorVersion();
    $environments = $this->project->getEnvironments();
    $current_environment = $environments[$current_environment_name];

    foreach ($environments as $environment_name => $_) {
      $commands->add(new EnsureDirectory("../localsettings/crontab.d/$environment_name"));
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

    // Write settings.custom.initial.php
    // This is not idempotent, and will break due to recursion if done twice.
    if (!file_exists('../localsettings/settings.custom.initial.php')) {
      $php = new PhpFile();
      // Transfer hash salt.
      foreach ($current_environment->getSiteUris() as $site => $_) {
        $settings = IncludeTool::getVariables("sites/$site/settings.php");
        if (!empty($settings['drupal_hash_salt'])) {
          $add_hash_salt = new PhpAssignment('$drupal_hash_salt', $settings['drupal_hash_salt']);
          if ($current_environment->isMultisite()) {
            $add_hash_salt = new PhpIf('', $add_hash_salt);
          }
          $php->addStatement($add_hash_salt);
        }
      }
      $commands->add(new WriteFile('../localsettings/settings.custom.initial.php', $php));
    }

    $commands->add(new WriteFile('../localsettings/settings.custom.additional.php', new PhpFile()));

    // Write aliases.drushrc.php alias
    $commands->add(new Symlink('../drush/aliases.drushrc.php', '../localsettings/aliases.drushrc.php'));

    // Write settings.custom.*.php
    foreach ($environments as $environment_name => $_) {
      $commands->add(new WriteFile("../localsettings/settings.custom.environment.$environment_name.php", new PhpFile()));
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
      CompileMisc::letEnvironmentsAlterHtaccess($commands, $this->project);
      CompileMisc::symlinkHtaccess($commands, $this->getCurrentEnvironmentName());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    CompileMisc::symlinkSettingsLocal($commands, $this->getCurrentEnvironmentName());
    CompileMisc::symlinkHtaccess($commands, $this->getCurrentEnvironmentName());

    return $commands;
  }

  public static function activateSite(Commands $commands, $site) {
    // Step 4
    CompileMisc::delegateSettings($commands, $site);
  }

}
