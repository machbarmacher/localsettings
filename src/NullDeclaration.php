<?php

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\Replacements;

final class NullDeclaration implements IDeclaration {

  /**
   * @var \machbarmacher\localsettings\Project
   */
  private $project;

  public function __construct(Project $project) {
    $this->project = $project;
  }

  public function getDeclarationName() {
    return 'NULL';
  }

  public function getEnvironmentName() {
    return 'NULL';
  }

  public function setEnvironmentName($environment_name) {
  }

  public function getProject() {
    return $this->project;
  }

  public function getServer() {
    return new NullServer();
  }

  public function getDocroot() {
    return 'NULL';
  }

  public function getSiteUris() {
    return [];
  }

  public function getUniqueSiteNameWithReplacements() {
    return 'NULL';
  }

  public function hasNonDefaultSite() {
    return FALSE;
  }

  public function validate() {
    return $this;
  }

  public function addSite($uri, $site = 'default') {
    return $this;
  }

  public function addSites($uris) {
    return $this;
  }

  public function addUri($uri, $site = 'default') {
    return $this;
  }

  public function setDocroot($docroot) {
    return $this;
  }

  public function setDbCredential($credentials, $site = 'default') {
    return $this;
  }

  public function setDbCredentialPattern($credential_pattern) {
    return $this;
  }

  public function setDrushEnvironmentVariable($name, $value) {
    return $this;
  }

  public function isLocal() {
    return FALSE;
  }

  public function isCurrent() {
    return FALSE;
  }

  public function alterHtaccess($content) {
    return $content;
  }

  public function compileSitesPhp(PhpFile $php) {
  }

  public function compileBaseUrls(PhpFile $php, Replacements $replacements) {
  }

  public function compileDbCredentials(PhpFile $php, Replacements $replacements) {
  }

  public function compileEnvironmentInfo(PhpFile $php, Replacements $replacements) {
  }

  public function compileAliases(PhpFile $php) {
  }

}
