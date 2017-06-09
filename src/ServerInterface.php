<?php
/**
 * @file ServerInterface.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

interface ServerInterface {
  public function getUser();
  public function getHost();
  public function getShortHostName();
  public function getUniqueAccountName();
  public function getUniqueInstallationName(Installation $installation);
  public function getDefaultDocroot();
  public function makeDocrootAbsolute($docroot);
  public function makeDocrootRelative($docroot);
  public function getUserHome();
  public function getWebHome();
  public function alterHtaccess($content);
  public function addSettings(PhpFile $php, Installation $installation);
  public function alterAlias(array $alias);
}
