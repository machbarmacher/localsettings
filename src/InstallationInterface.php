<?php
/**
 * @file InstallationInterface.php
 */

namespace clever_systems\mmm-builder;


interface InstallationInterface {
  public function getAliases();
  public function getUriToSiteMap();
  public function getDbCredentials();
  public function getBaseUrls();
}
