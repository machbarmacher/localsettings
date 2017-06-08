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
use machbarmacher\localsettings\RenderPhp\PhpFile;

class Compiler {
  /** @var Project */
  protected $project;

  protected function getInstallationName() {
    return 'dev'; // @todo
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

  /**
   * @return string[]
   */
  public function getInstallationNames() {
    $environment_names = [];
    foreach ($this->project->getInstallations() as $installation) {
      $environment_names[] = $installation->getName();
    }
    return $environment_names;
  }

  public function compile(Commands $commands) {
    $drush_dir = ($this->project->getDrupalMajorVersion() == 8) ?
      '../drush' : 'sites/all/drush';

    $sitesPhp = $this->compileSitesPhp();
    if (!$sitesPhp->empty()) {
      $commands->add(new WriteFile('sites/sites.php', $sitesPhp));
    }
    $commands->add(new WriteFile("$drush_dir/aliases.drushrc.php", $this->compileAliases()));
    $commands->add(new WriteFile('../localsettings/settings.baseurl.php', $this->compileBaseUrls()));
    $commands->add(new WriteFile('../localsettings/settings.databases.php', $this->compileDbCredentials()));
  }

  public function compileAliases() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: Aliases');
    $php->addToHeader('include __DIR__ . \'/../vendor/autoload.php\';');
    $php->addToHeader('use clever_systems\mmm_runtime\Runtime;');

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileAliases($php);
    }
    return $php;
  }

  public function compileSitesPhp() {
    // @todo Consider writing a file per installation and symlinking it.
    $php = new PhpFile();
    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileSitesPhp($php);
    }
    return $php;
  }

  public function compileBaseUrls() {
    $settings_variable = $this->project->getSettingsVariable();
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: Base Urls');
    $php->addToHeader('use clever_systems\mmm_runtime\Runtime;');
    if ($this->project->getDrupalMajorVersion() == 7) {
      $php->addToHeader("\$host = rtrim(\$_SERVER['HTTP_HOST'], '.');");
    }

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileBaseUrls($php);
    }

    $php->addToFooter("if (empty({$settings_variable}['mmm']['installation'])) error_log('MMM Unknown host or site Id: ' . Runtime::getEnvironment()->getNormalizedSiteUrn());");
    return $php;
  }

  public function compileDbCredentials() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: DB Credentials');
    $php->addToHeader('use clever_systems\mmm_runtime\Runtime;');

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileDbCredentials($php);
    }
    return $php;
  }

  public function letInstallationsAlterHtaccess(Commands $commands) {
    $original_file = !drush_get_option('simulate')
      // Not simulated? Look at the correct location.
      ? '.htaccess.original'
      // Simulated ? Look at the previous location.
      : '.htaccess';
    foreach ($this->project->getInstallations() as $installation) {
      $installation_name = $installation->getName();
      $commands->add(new AlterFile($original_file, ".htaccess.$installation_name",
        function ($content) use ($installation) {
          return $installation->alterHtaccess($content);
        }));
    }
  }

  public function writeSettingsLocal(Commands $commands, $current_installation_name) {
    $installation_names = $this->getInstallationNames();
    foreach ($installation_names as $installation_name) {
      $content = ($installation_name === $current_installation_name)
        ? file_get_contents('sites/default/settings.php')
        . "\n\n// TODO: Clean up." : "<?php\n";
      // @todo Remove comments.
      $commands->add(new  WriteFile("../localsettings/settings.local.$installation_name.php", $content));
    }
  }

  public static function prepare(Commands $commands) {
    // Step 1
    Scaffolder::writeProject($commands);
    // Step 2 is compiling
  }

  public function scaffold(Commands $commands, $current_installation_name) {
    $drupal_major_version = $this->getProject()->getDrupalMajorVersion();
    // Step 3
    $installation_names = $this->getInstallationNames();

    foreach ($installation_names as $installation_name) {
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

    $commands->add(new WriteFile('../settings.common.php', "<?php\n"));

    $this->writeSettingsLocal($commands, $current_installation_name);

    Scaffolder::writeSettings($commands, $drupal_major_version);

    Scaffolder::writeBoxfile($commands);

    Scaffolder::writeGitignoreForComposer($commands);

    $this->postClone($commands);
    $this->postUpdate($commands);
  }

  public static function preUpdate(Commands $commands) {
    // Prepare update and scaffold of docroot.
    // Make htacces a file again, not a symlink.
    Scaffolder::moveBackHtaccess($commands);
  }

  public function postUpdate(Commands $commands) {
    // A docroot update brought upstream versions:
    // Use our gitignore; Alter and symlink htaccess.
    Scaffolder::writeGitignoreForDrupal($commands);
    if (file_exists('.htaccess') && !is_link('.htaccess')) {
      Scaffolder::moveAwayHtaccess($commands);
      $this->letInstallationsAlterHtaccess($commands);
      Scaffolder::symlinkHtaccess($commands, $this->getInstallationName());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    Scaffolder::symlinkSettingsLocal($commands, $this->getInstallationName());
    Scaffolder::symlinkHtaccess($commands, $this->getInstallationName());

    return $commands;
  }

  public static function activateSite(Commands $commands, $site) {
    // Step 4
    Scaffolder::delegateSettings($commands, $site);
  }
}
