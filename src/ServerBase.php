<?php
/**
 * @file ServerBase.php
 */

namespace machbarmacher\localsettings;


use machbarmacher\localsettings\RenderPhp\PhpFile;

abstract class ServerBase implements ServerInterface {
  public function makeDocrootAbsolute($docroot) {
    if (substr($docroot, 0, 1) !== '/') {
      $docroot = $this->getWebHome() . '/' . $docroot;
    }
    return $docroot;
  }

  public function makeDocrootRelative($docroot) {
    $webhome = $this->getWebHome() . '/';
    $webhome_len = strlen($webhome);
    if (substr($docroot, 0, $webhome_len) == $webhome) {
      $docroot = substr($docroot, $webhome_len);
    }
    return $docroot;
  }

  public function getUserHome() {
    return '/home/' . $this->getUser();
  }

  public function getShortHostName() {
    return $this->getHost();
  }

  public function getUniqueAccountName() {
    return $this->getUser() . '@' . $this->getShortHostName();
  }

  public function getUniqueInstallationName(Installation $installation) {
    $account_name = $this->getUniqueAccountName();
    $docroot = $this->makeDocrootRelative($installation->getDocroot());
    return "$account_name:$docroot";
  }

  public function addInstallationSpecificSettings(PhpFile $php, Installation $installation) {
    $settings_variable = $installation->getProject()->getSettingsVariable();
    // $site is a placeholder here that uses the variable defined in settings.
    $unique_site_name = $installation->getUniqueSiteName('$site');

    $php->addRawStatement(<<<EOD
{$settings_variable}['cache_prefix']['default'] = "$unique_site_name";
EOD
    );
  }

  public function addServerSpecificSettings(PhpFile $php, Project $project) {
  }

  public function alterAlias(array &$alias) {
  }

  public function getLocalServerCheck($host_expression, $user_expression) {
    return "($host_expression == gethostname()) && ($user_expression == get_current_user())";
  }

}
