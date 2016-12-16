<?php
/**
 * @file InstallationInterface.php
 */

namespace clever_systems\mmm2;


interface InstallationInterface {
  public function getAliases();
  public function getUriToSiteMap();
  public function getDbCredentials();
  public function getBaseUrls();
}
