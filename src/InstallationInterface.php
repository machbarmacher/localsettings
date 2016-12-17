<?php
/**
 * @file InstallationInterface.php
 */

namespace clever_systems\mmm_builder;


interface InstallationInterface {
  public function getAliases();
  public function getUriToSiteMap();
  public function getDbCredentials();
  public function getBaseUrls();
}
