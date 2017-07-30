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
  public function getUniqueInstallationName(IEnvironment $environment);
  public function getDefaultDocroot();
  public function makeDocrootAbsolute($docroot);
  public function makeDocrootRelative($docroot);
  public function getUserHome();
  public function getWebHome();
  public function alterHtaccess($content);
  public function addEnvironmentSpecificSettings(PhpFile $php, IEnvironment $environment);
  public function addServerSpecificSettings(PhpFile $php, Project $project);
  public function alterAlias(array &$alias);
  public function getLocalServerCheck($host_expression, $user_expression);
}
