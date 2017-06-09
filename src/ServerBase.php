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

  public function addSettings(PhpFile $php, Installation $installation) {
    $settings_variable = $installation->getProject()->getSettingsVariable();
    // $site is a placeholder here that uses the variable defined in settings.
    $server_unique_site_name = $installation->getServerUniqueSiteName('$site');

    $php->addToBody(<<<EOD
{$settings_variable}['cache_prefix']['default'] = "$server_unique_site_name";
EOD
    );
  }

  public function alterAlias(array $alias) {
    // @todo Add #localsettings_current_installation and make this alias.
    // @todo Remove remote-host/user from local aliases.
  }
}
