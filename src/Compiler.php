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
    $this->addAliasAlterCode($php);
    $commands->add(new WriteFile("../localsettings/aliases.drushrc.php", $php));

    $server_setting_files = [];
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $canonical_installation_name = $installation->getCanonicalName();
      $php = new PhpFile();
      $php->addRawStatement('// Basic installation facts.');
      $this->addInstallationFacts($php, $installation);
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
    $this->addBasicFacts($php);
    $commands->add(new WriteFile("../localsettings/settings.generated-basic.php", $php));

    $php = new PhpFile();
    $this->addGenericSettings($php);
    $commands->add(new WriteFile("../localsettings/settings.generated-common.php", $php));
  }

  protected function addInstallationFacts(PhpFile $php, Installation $installation) {
    $settings_variable = $this->project->getSettingsVariable();

    $installation_name = $installation->getName();
    $unique_site_name  = $installation->getUniqueSiteName('$site');

    $php->addRawStatement(<<<EOD
\$installation = {$settings_variable}['localsettings']['installation'] = '$installation_name';
\$unique_site_name = {$settings_variable}['localsettings']['unique_site_name'] = "$unique_site_name";
EOD
    );
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  protected function addAliasAlterCode(PhpFile $php) {
    $local_server_checks = [];
    foreach ($this->project->getInstallations() as $installation_name => $installation) {
      $check = $installation->getServer()->getLocalServerCheck("\$alias['remote-host']", "\$alias['remote-user']");
      $local_server_checks[$check] = $check;
    }
    $local_server_check_statements = new PhpStatements();
    foreach ($local_server_checks as $local_server_check) {
      $local_server_check_statements->addStatement(new PhpRawStatement(
        "  \$is_local = \$is_local || $local_server_check;"
      ));
    }

    $php->addRawStatement(<<<EOD

// Alter local aliases and add @this here as the drush alter hook is broken. 
\$current_sites = [];
foreach (\$aliases as \$alias_name => &\$alias) {
  if (!isset(\$alias['remote-host']) || !isset(\$alias['remote-user'])) {
    continue;
  }
  \$is_local = FALSE;
$local_server_check_statements
  if (\$is_local) {
    unset(\$alias['remote-host']);
    unset(\$alias['remote-user']);
  }

  \$is_current = \$is_local && realpath(\$alias['root']) == realpath(DRUSH_DRUPAL_CORE);
  if (\$is_current) {
    \$current_sites["@\$alias_name"] = \$alias;
  }
}
if (count(\$current_sites) == 1) {
  \$aliases['this'] = reset(\$current_sites);
}
else {
  \$aliases['this'] = ['site-list' => array_keys(\$current_sites)];
}

EOD
    );
  }

  protected function addBasicFacts(PhpFile $php) {
    $is_d7 = $this->project->isD7();
    $settings_variable = $this->project->getSettingsVariable();
    $conf_path = $is_d7 ? 'conf_path()' : '\Drupal::service(\'site.path\')->get()';

    $php->addRawStatement(<<<EOD
\$site = {$settings_variable}['localsettings']['site'] = basename($conf_path);
\$dirname = {$settings_variable}['localsettings']['dirname'] = basename(dirname(getcwd()));
EOD
    );
  }

  protected function addGenericSettings(PhpFile $php) {
    $is_d7 = $this->project->isD7();
    $settings_variable = $this->project->getSettingsVariable();

    $tmp_path_quoted = "\"../tmp/\$site\"";
    $private_path_quoted = "\"../private/\$site\"";

    $php->addRawStatement(<<<EOD
{$settings_variable}['file_public_path'] = "sites/\$site/files";

if (!file_exists({$private_path_quoted})) { mkdir({$private_path_quoted}); }    
{$settings_variable}['file_private_path'] = {$private_path_quoted};

if (!file_exists({$tmp_path_quoted})) { mkdir({$tmp_path_quoted}); }   
EOD
    );

    // @fixme Add unique name method and tokens.
    if ($is_d7) {
      $php->addRawStatement(<<<EOD
\$conf['file_temporary_path'] = {$tmp_path_quoted};

\$conf['environment_indicator_overwrite'] = TRUE;
\$conf['environment_indicator_overwritten_name'] = \$unique_site_name;
\$conf['environment_indicator_overwritten_color'] = '#' . dechex(hexdec(substr(md5(\$conf['environment_indicator_overwritten_name']), 0, 6)) & 0x7f7f7f); // Only dark colors.
\$conf['environment_indicator_overwritten_text_color'] = '#ffffff';
EOD
      );
    }
    else {
      $php->addRawStatement(<<<EOD
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

  public static function prepare(Commands $commands) {
    // Step 1
    Scaffolder::writeProject($commands);
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

    Scaffolder::writeSettings($commands, $drupal_major_version);

    // @todo Delegate to server.
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
