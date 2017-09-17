<?php
/**
 * @file IDeclaration.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\Tools\Replacements;

interface IDeclaration {
  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php);

  public function isCurrent();

  /**
   * Declaration constructor.
   *
   * @param string $declaration_name
   * @param IServer $server
   * @param Project $project
   */
  public function __construct($declaration_name, IServer $server, Project $project);

  /**
   * @return string
   */
  public function getDeclarationName();

  /**
   * @return string
   */
  public function getEnvironmentName();

  /**
   * @param string $environment_name
   * @return $this
   */
  public function setEnvironmentName($environment_name);

  /**
   * @return \machbarmacher\localsettings\Project
   */
  public function getProject();

  /**
   * @return \machbarmacher\localsettings\IServer
   */
  public function getServer();

  /**
   * @return string
   */
  public function getDocroot();

  /**
   * @return \string[][]
   */
  public function getSiteUris();

  public function getUniqueSiteNameWithReplacements();

  public function hasNonDefaultSite();

  /**
   * @return $this
   */
  public function validate();

  /**
   * @param string $uri
   * @param string $site
   * @return $this
   */
  public function addSite($uri, $site = 'default');

  /**
   * @param string $uri
   * @param string $site
   * @return $this
   */
  public function addUri($uri, $site = 'default');

  /**
   * @param string $docroot
   * @return $this
   */
  public function setDocroot($docroot);

  /**
   * @param array|string $credentials
   * @param string $site
   * @return $this
   */
  public function setDbCredential($credentials, $site = 'default');

  /**
   * Set DB credentials that vary by
   * - {{site}}: replaced instantly for all previously(!) defined sites
   * - {{installation}}: installation name, replaced on runtime for InstallationGlobber
   * @param array|string $credential_pattern
   * @return $this
   */
  public function setDbCredentialPattern($credential_pattern);

  /**
   * @param string $name
   * @param string $value
   */
  public function setDrushEnvironmentVariable($name, $value);

  /**
   * @return bool
   */
  public function isLocal();

  public function alterHtaccess($content);

  public function compileSitesPhp(PhpFile $php);

  public function compileBaseUrls(PhpFile $php, Replacements $replacements);

  public function compileDbCredentials(PhpFile $php, Replacements $replacements);

  public function compileEnvironmentInfo(PhpFile $php, Replacements $replacements);
}
