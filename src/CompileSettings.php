<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\Replacements;

class CompileSettings {

  public static function addInitialSettings(PhpFile $php, Replacements $replacements, Project $project) {
    $is_d7 = $project->isD7();
    $settings_variable = $project->getSettingsVariable();
    $conf_path = $is_d7 ? 'conf_path()' : '\Drupal::service(\'site.path\')->get()';

    $php->addRawStatement(<<<EOD
\$site = {$settings_variable}['localsettings']['site'] = basename($conf_path);
\$dirname = {$settings_variable}['localsettings']['dirname'] = basename(dirname(getcwd()));

EOD
    );
    $replacements->register('{{site}}', '{$site}');
    $replacements->register('{{dirname}}', '{$dirname}');

    $is_d7 = $project->isD7();
    $settings_variable = $project->getSettingsVariable();

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

  public static function addAdditionalSettings(PhpFile $php, Replacements $replacements, Project $project) {
    $is_d7 = $project->isD7();
    if ($is_d7) {
      $php->addRawStatement(<<<EOD
\$conf['environment_indicator_overwrite'] = TRUE;
\$conf['environment_indicator_overwritten_name'] = \$unique_site_name;
\$conf['environment_indicator_overwritten_color'] = '#' . dechex(hexdec(substr(md5(\$conf['environment_indicator_overwritten_name']), 0, 6)) & 0x7f7f7f); // Only dark colors.
\$conf['environment_indicator_overwritten_text_color'] = '#ffffff';
EOD
      );
    }
  }

}
