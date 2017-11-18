<?php
/**
 * @file ServerInterface.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\Replacements;

interface IServer {
  public function getUser();
  public function getHost();
  public function getPort();
  public function getTypeName();
  public function getShortHostName();
  public function getUniqueAccountName();
  public function getUniqueInstallationName(IDeclaration $declaration);
  public function getDefaultDocroot();
  public function makeDocrootAbsolute($docroot);
  public function makeDocrootRelative($docroot);
  public function getUserHome();
  public function getWebHome();
  public function alterHtaccess($content);
  public function addEnvironmentSpecificSettings(PhpFile $php, Replacements $replacements, IDeclaration $declaration);
  public function addServerSpecificSettings(PhpFile $php, Replacements $replacements, Project $project);
  public function alterAlias(array &$alias);
  public function getRuntimeIsLocalCheck();
}
