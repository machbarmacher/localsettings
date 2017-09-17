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
use machbarmacher\localsettings\RenderPhp\Assignment;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\RenderPhp\IfThen;
use machbarmacher\localsettings\RenderPhp\PhpRawStatement;
use machbarmacher\localsettings\RenderPhp\Statements;
use machbarmacher\localsettings\Tools\IncludeTool;
use machbarmacher\localsettings\Tools\Replacements;

class Compiler {
  /** @var Project */
  protected $project;

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
    // Generate sites.php
    $php = new PhpFile();
    foreach ($this->project->getDeclarations() as $declaration) {
      if ($declaration->hasNonDefaultSite()) {
        $declaration->compileSitesPhp($php);
      }
    }
    // Only write if we have a multisite.
    if (!$php->isEmpty()) {
      $commands->add(new WriteFile('../localsettings/sites.php', $php));
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    // Generate aliases.drushrc.php
    $php = new PhpFile();
    $php->addRawStatement('$aliases = [];');
    foreach ($this->project->getDeclarations() as $declaration) {
      $declaration->compileAliases($php);
    }
    CompileAliases::addAliasAlterCode($php, $this->project->getDeclarations());
    $commands->add(new WriteFile("../localsettings/aliases.drushrc.php", $php));

    // Carry replacements usable in settings.*.php
    $replacementsInitial = new Replacements();

    // Generate settings.generated.initial.php
    $php = new PhpFile();
    CompileSettings::addInitialSettings($php, $replacementsInitial, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated.initial.php", $php));

    // Generate settings.generated.declaration.FOO.php
    $server_setting_files = [];
    foreach ($this->project->getDeclarations() as $declaration) {
      $declaration_name = $declaration->getDeclarationName();
      $php = new PhpFile();
      $replacements = clone $replacementsInitial;
      $php->addRawStatement('// Environment info.');
      $declaration->compileEnvironmentInfo($php, $replacements);
      $php->addRawStatement('');
      $php->addRawStatement('// Base URLs');
      $declaration->compileBaseUrls($php, $replacements);
      $php->addRawStatement('');
      $php->addRawStatement('// DB Credentials');
      $declaration->compileDbCredentials($php, $replacements);
      $php->addRawStatement('');
      $php->addRawStatement('// Environment specific');
      $declaration->getServer()->addEnvironmentSpecificSettings($php, $replacements, $declaration);
      $php->addRawStatement('');
      $php->addRawStatement('// Server specific');
      $server = $declaration->getServer();
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

    // Generate settings.generated.additional.php
    $php = new PhpFile();
    CompileSettings::addAdditionalSettings($php, $replacementsInitial, $this->project);
    $commands->add(new WriteFile("../localsettings/settings.generated.additional.php", $php));

    // Write boxfile.
    CompileMisc::writeBoxfile($commands, $this->project);
  }

  public static function prepare(Commands $commands) {
    // Step 1
    CompileMisc::writeProject($commands);
    // Step 2 is compiling
  }

  public function scaffold(Commands $commands, $current_declaration_name) {
    // Step 3
    $drupal_major_version = $this->getProject()->getDrupalMajorVersion();

    // Drupal composer project uses web directory like symfony,
    // so we always ensure a docroot symlink.
    if (!file_exists('../docroot')) {
      if (is_dir('../web')) {
        // We need the symlink later.
        (new Symlink('../docroot', 'web'))->execute();
      }
      else {
        throw new \Exception('Found neither docroot nor web directory.');
      }
    }

    $current_declaration = $this->project->getDeclaration($current_declaration_name);

    foreach ($this->project->getEnvironmentNames() as $environment_name) {
      $commands->add(new EnsureDirectory("../localsettings/crontab.d/$environment_name"));
    }
    $commands->add(new EnsureDirectory("../localsettings/crontab.d/common"));
    $commands->add(new WriteFile("../localsettings/crontab.d/common/50-cron",
      "0 * * * * drush -r \$DRUPAL_ROOT cron -y\n"));

    if ($drupal_major_version != 7) {
      $commands->add(new WriteFile('../config-sync/.gitkeep', ''));
    }

    // Write copy s/*/m/settings.php to settings.custom.initial.php
    if (!file_exists('../localsettings/settings.custom.initial.php')) {
      $php = new PhpFile();
      // Copy settings if not already done, but mask them with a return.
      $php->addRawStatement('return; // TODO: Clean up and remove this.');
      $php->addRawStatement('');
      $siteUris = $current_declaration->getSiteUris();
      $multisite = count($siteUris) > 1;
      foreach ($siteUris as $site => $_) {
        $settings_php = file_exists("sites/$site/settings.php") ?
          file_get_contents("sites/$site/settings.php") :
          file_get_contents("sites/default/default.settings.php");
        $settings_php = preg_replace("/^<\?php\s*/\n", '', $settings_php);
        if ($multisite) {
          $php->addRawStatement("if (\$site === '$site') {");
        }
        $php->addRawStatement($settings_php);
        if ($multisite) {
          $php->addRawStatement("}");
        }
      }
      $commands->add(new WriteFile('../localsettings/settings.custom.initial.php', $php));
    }

    if (!file_exists('../localsettings/settings.custom.additional.php')) {
      $commands->add(new WriteFile('../localsettings/settings.custom.additional.php', new PhpFile()));
    }

    // Write aliases.drushrc.php alias
    $aliases_file_location = $this->project->isD7() ?
      'sites/all/drush/aliases.drushrc.php' : '../drush/aliases.drushrc.php';
    $commands->add(new Symlink($aliases_file_location, '../localsettings/aliases.drushrc.php'));

    // Write sites.php alias if needed.
    if (file_exists('../localsettings/sites.php')) {
      $commands->add(new Symlink('sites/sites.php', '../../localsettings/sites.php'));
    }

    // Write settings.custom.environment.*.php
    foreach ($this->project->getEnvironmentNames() as $environment_name) {
      if (!file_exists("../localsettings/settings.custom.environment.$environment_name.php")) {
        $commands->add(new WriteFile("../localsettings/settings.custom.environment.$environment_name.php", new PhpFile()));
      }
    }

    CompileMisc::writeSettings($commands, $drupal_major_version);

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
      CompileMisc::symlinkHtaccessPerEnvironment($commands, $this->project->getCurrentDeclaration()->getEnvironmentName());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    $current_declaration = $this->project->getCurrentDeclaration();
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
