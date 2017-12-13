<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\Replacements;

final class NullServer implements IServer {
  public function getUser() {
    return 'NULL';
  }

  public function getHost() {
    return 'NULL';
  }

  public function getPort() {
    return 'NULL';
  }

  public function getTypeName() {
    return 'NULL';
  }

  public function getShortHostName() {
    return 'NULL';
  }

  public function getUniqueAccountName() {
    return 'NULL';
  }

  public function getUniqueInstallationName(IDeclaration $declaration) {
    return 'NULL';
  }

  public function getDefaultDocroot() {
    return 'NULL';
  }

  public function makeDocrootAbsolute($docroot) {
    return 'NULL';
  }

  public function makeDocrootRelative($docroot) {
    return 'NULL';
  }

  public function getUserHome() {
    return 'NULL';
  }

  public function getWebHome() {
    return 'NULL';
  }

  public function alterHtaccess($content) {
    return $content;
  }

  public function addEnvironmentSpecificSettings(PhpFile $php, Replacements $replacements, IDeclaration $declaration) {
  }

  public function addServerSpecificSettings(PhpFile $php, Replacements $replacements, Project $project) {
  }

  public function alterAlias(array &$alias) {
  }

  public function getRuntimeIsLocalCheck() {
    return 'FALSE';
  }

}
