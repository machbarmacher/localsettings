<?php
/**
 * @file IEnvironment.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

interface IEnvironment {
  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php);

  public function isCurrent();

  /**
   * Environment constructor.
   *
   * @param string $name
   * @param IServer $server
   * @param Project $project
   */
  public function __construct($name, IServer $server, Project $project);

  /**
   * @return string
   */
  public function getName();

  /**
   * @return string
   */
  public function getEnvironmentName();

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

  public function getUniqueSiteName($site);

  public function isMultisite();

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
   * - {{installation}}: installation name, replaced on runtime for MultiEnvironment
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

  public function compileBaseUrls(PhpFile $php);

  public function compileDbCredentials(PhpFile $php);

  public function compileEnvironmentInfo(PhpFile $php);
}
