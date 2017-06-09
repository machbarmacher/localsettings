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

  protected function getCurrentInstallationName() {
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

  public function compile(Commands $commands) {
    // @todo Consider writing a sites.INSTALLATION.php and symlinking it.
    $php = new PhpFile();
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      if ($installation->isMultisite()) {
        $installation->compileSitesPhp($php);
      }
    }
    // Only write if we have a multisite.
    if (!$php->empty()) {
      $commands->add(new WriteFile('sites/sites.php', $php));
    }

    $php = new PhpFile();
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $installation->compileAliases($php);
    }
    $commands->add(new WriteFile("../drush/aliases.drushrc.php", $php));

    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $php = new PhpFile();
      $php->addToBody('// Generic settings');
      $this->addBasicSettings($php, $installation);
      $this->addGenericSettings($php);
      $php->addToBody('');
      $php->addToBody('// Base URLs');
      $installation->compileBaseUrls($php);
      $php->addToBody('');
      $php->addToBody('// DB Credentials');
      $installation->compileDbCredentials($php);
      $php->addToBody('');
      $php->addToBody('// Server specific');
      $installation->getServer()->addSettings($php, $installation);
      $commands->add(new WriteFile("../localsettings/settings.generated.{$installation_name}.php", $php));
    }
  }

  protected function addBasicSettings(PhpFile $php, Installation $installation) {
    $settings_variable = $this->project->getSettingsVariable();

    $installation_name = $installation->getName();
    $unique_site_name  = $installation->getUniqueSiteName('$site');

    $php->addToBody(<<<EOD
\$installation = {$settings_variable}['localsettings']['installation'] = '$installation_name';
\$unique_site_name = {$settings_variable}['localsettings']['unique_site_name'] = "$unique_site_name";
EOD
    );
  }

  protected function addGenericSettings(PhpFile $php) {
    $is_d7 = $this->project->isD7();
    $settings_variable = $this->project->getSettingsVariable();
    $conf_path = $is_d7 ? 'conf_path()' : '\Drupal::service(\'site.path\')->get()';

    $tmp_path_quoted = "\"../tmp/\$site\"";
    $private_path_quoted = "\"../private/\$site\"";

    $php->addToBody(<<<EOD
\$site = {$settings_variable}['localsettings']['site'] = basename($conf_path);
\$dirname = {$settings_variable}['localsettings']['dirname'] = basename(dirname(getcwd()));

{$settings_variable}['file_public_path'] = "sites/\$site/files";

if (!file_exists({$private_path_quoted})) { mkdir({$private_path_quoted}); }    
{$settings_variable}['file_private_path'] = {$private_path_quoted};

if (!file_exists({$tmp_path_quoted})) { mkdir({$tmp_path_quoted}); }   
EOD
    );

    // @fixme Add unique name method and tokens.
    if ($is_d7) {
      $php->addToBody(<<<EOD
\$conf['file_temporary_path'] = {$tmp_path_quoted};

\$conf['environment_indicator_overwrite'] = TRUE;
\$conf['environment_indicator_overwritten_name'] = \$unique_site_name;
\$conf['environment_indicator_overwritten_color'] = '#' . dechex(hexdec(substr(md5(\$conf['environment_indicator_overwritten_name']), 0, 6)) & 0x7f7f7f); // Only dark colors.
\$conf['environment_indicator_overwritten_text_color'] = '#ffffff';
EOD
      );
    }
    else {
      $php->addToBody(<<<EOD
global \$config;
\$config['system.file']['path']['temporary'] = {$tmp_path_quoted};

global \$config_directories;
\$config_directories[CONFIG_SYNC_DIRECTORY] = '../config-sync';
EOD
      );
    }

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
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
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

    foreach ($this->project->getInstallations() as $installation_name => $installation) {
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

    $commands->add(new WriteFile('../localsettings/settings.common.php', "<?php\n"));

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
      Scaffolder::symlinkHtaccess($commands, $this->getCurrentInstallationName());
    }

    return $commands;
  }

  public function postClone(Commands $commands) {
    // A git clone "forgot" all .gitignored files and folders.
    // @fixme Add tmp/default etc.
    $commands->add(new EnsureDirectory('../private'));
    $commands->add(new EnsureDirectory('../tmp'));
    $commands->add(new EnsureDirectory('../logs'));

    Scaffolder::symlinkSettingsLocal($commands, $this->getCurrentInstallationName());
    Scaffolder::symlinkHtaccess($commands, $this->getCurrentInstallationName());

    return $commands;
  }

  public static function activateSite(Commands $commands, $site) {
    // Step 4
    Scaffolder::delegateSettings($commands, $site);
  }
}
