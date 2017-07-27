<?php
/**
 * @file InstallationInterface.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\RenderPhp\PhpFile;

interface InstallationInterface {
  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpFile $php
   */
  public function compileAliases(PhpFile $php);

  public function isCurrent();

  /**
   * Installation constructor.
   *
   * @param string $name
   * @param ServerInterface $server
   * @param Project $project
   */
  public function __construct($name, ServerInterface $server, Project $project);

  /**
   * @return string
   */
  public function getName();

  /**
   * @return \machbarmacher\localsettings\Project
   */
  public function getProject();

  /**
   * @return \machbarmacher\localsettings\ServerInterface
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
   * - {{installation}}: installation name, replaced instantly, just convenience
   * - {{dirname}}: installation directory name, replaced on runtime for cluster
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
}