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

  protected function getCurrentDeclaration() {
    foreach ($this->project->getDeclarations() as $declaration) {
      if ($declaration->isCurrent()) {
        return $declaration;
      }
    }
    throw new \Exception('Can not recognize current declaration.');
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
    foreach ($this->project->getDeclarations() as $declaration) {
      if ($declaration->isMultisite()) {
        $declaration->compileSitesPhp($php);
      }
    }
    // Only write if we have a multisite.
    if (!$php->isEmpty()) {
      $commands->add(new WriteFile('../localsettings/sites.php', $php));
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    $php = new PhpFile();
    $php->addRawStatement('$aliases = [];');
    foreach ($this->project->getDeclarations() as $declaration) {
      $declaration->compileAliases($php);
    }
    CompileAliases::addAliasAlterCode($php, $this->project->getDeclarations());
    $commands->add(new WriteFile("../localsettings/aliases.drushrc.php", $php));

    $server_setting_files = [];
    foreach ($this->project->getDeclarations() as $declaration) {
      $declaration_name = $declaration->getDeclarationName();
      $php = new PhpFile();
      $php->addRawStatement('// Environment info.');
      $declaration->compileEnvironmentInfo($php);
      $php->addRawStatement('');
      $php->addRawStatement('// Base URLs');
      $declaration->compileBaseUrls($php);
      $php->addRawStatement('');
      $php->addRawStatement('// DB Credentials');
      $declaration->compileDbCredentials($php);
      $php->addRawStatement('');
      $php->addRawStatement('// Environment specific');
      $declaration->getServer()->addEnvironmentSpecificSettings($php, $declaration);
      $php->addRawStatement('');
      $php->addRawStatement('// Server specific');
      $server = $declaration->getServer();
      // @fixme Collect and do afterwards.
      $server_name = $server->getTypeName();
      if (!isset($server_setting_files[$server_name])) {
        $server_php = new PhpFile();
        $server->addServerSpecificSettings($server_php, $this->project);
        $server_setting_file = "settings.generated.server.$server_name.php";
        $server_setting_files[$server_name] = $server_setting_file;
        $commands->add(new WriteFile("../localsettings/$server_setting_file", $server_php));
      }
      $php->addRawStatement("include '../localsettings/{$server_setting_files[$server_name]}';");
      $commands->add(new WriteFile("../localsettings/settings.generated.declaration.{$declaration_name}.php", $php));
    }

    $php = new PhpFile();
    CompileSettings::addInitialSettings($php, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated.initial.php", $php));

    $php = new PhpFile();
    CompileSettings::addAdditionalSettings($php, $this->project);
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
    $declarations = $this->project->getDeclarations();
    // @fixme This is now a declaration name.
    $current_environment = $declarations[$current_environment_name];

    foreach ($declarations as $environment_name => $_) {
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
    // @todo Copy settings if not already done, but mask them with a return.
    if (!file_exists('../localsettings/settings.custom.initial.php')) {
      $php = new PhpFile();
      // Transfer hash salt.
      // @todo Consider making sites a project setting.
      foreach ($current_environment->getSiteUris() as $site => $_) {
        $settings = IncludeTool::getVariables("sites/$site/settings.php");
        if (!empty($settings['drupal_hash_salt'])) {
          $add_hash_salt = new PhpAssignment('$drupal_hash_salt', $settings['drupal_hash_salt']);
          if ($current_environment->isMultisite()) {
            // @fixme Looks like unfinished.
            $add_hash_salt = new PhpIf('', $add_hash_salt);
          }
          $php->addStatement($add_hash_salt);
        }
      }
      $commands->add(new WriteFile('../localsettings/settings.custom.initial.php', $php));
    }

    $commands->add(new WriteFile('../localsettings/settings.custom.additional.php', new PhpFile()));

    // Write aliases.drushrc.php alias
    $aliases_file_location = $current_environment->getProject()->isD7() ?
      'sites/all/drush/aliases.drushrc.php' : '../drush/aliases.drushrc.php';
    $commands->add(new Symlink($aliases_file_location, '../localsettings/aliases.drushrc.php'));

    // Write sites.php alias if needed.
    if (file_exists('../localsettings/sites.php')) {
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    // Write settings.custom.environment.*.php
    foreach ($declarations as $environment_name => $_) {
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
    CompileMisc::writeGitignoreForDrupalRoot($commands);
    if (file_exists('.htaccess') && !is_link('.htaccess')) {
      CompileMisc::moveAwayHtaccess($commands);
      CompileMisc::letDeclarationsAlterHtaccess($commands, $this->project);
      CompileMisc::symlinkHtaccessPerEnvironment($commands, $this->getCurrentDeclaration());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    $current_declaration = $this->getCurrentDeclaration();
    CompileMisc::symlinkSettingsGeneratedPerDeclaration($commands, $current_declaration->getDeclarationName());
    CompileMisc::symlinkSettingsCustomPerEnvironment($commands, $current_declaration->getEnvironmentName());
    CompileMisc::symlinkHtaccessPerEnvironment($commands, $current_declaration->getEnvironmentName());

    return $commands;
  }

  public static function activateSite(Commands $commands, $site) {
    // Step 4
    CompileMisc::delegateSettings($commands, $site);
  }

}
