<?php
/**
 * @file ServerInterface.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

interface IServer {
  public function getUser();
  public function getHost();
  public function getTypeName();
  public function getShortHostName();
  public function getUniqueAccountName();
  public function getUniqueInstallationName(IEnvironment $installation);
  public function getDefaultDocroot();
  public function makeDocrootAbsolute($docroot);
  public function makeDocrootRelative($docroot);
  public function getUserHome();
  public function getWebHome();
  public function alterHtaccess($content);
  public function addInstallationSpecificSettings(PhpFile $php, IEnvironment $installation);
  public function addServerSpecificSettings(PhpFile $php, Project $project);
  public function alterAlias(array &$alias);
  public function getLocalServerCheck($host_expression, $user_expression);
}
