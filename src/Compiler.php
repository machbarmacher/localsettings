<?php
/**
 * @file Compiler.php
 */

namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\WriteFile;
use clever_systems\mmm_builder\RenderPhp\PhpFile;

class Compiler {
  /** @var Project */
  protected $project;

  /**
   * Compiler constructor.
   * @param Project $project
   */
  public function __construct(Project $project) {
    $this->project = $project;
  }

  /**
   * @return Commands
   */
  public function compile() {
    $drush_dir = ($this->project->getDrupalMajorVersion() == 8) ?
      'drush' : 'sites/all/drush';

    $commands = new Commands();
    $commands->add(new WriteFile('sites/sites.php', $this->compileSitesPhp()));
    $commands->add(new WriteFile("$drush_dir/aliases.drushrc.php", $this->compileAliases()));
    $commands->add(new WriteFile('../settings.baseurl.php', $this->compileBaseUrls()));
    $commands->add(new WriteFile('../settings.databases.php', $this->compileDbCredentials()));
  }

  public function compileAliases() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: Aliases');

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileAliases($php);
    }
    return (string)$php;
  }

  public function compileSitesPhp() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: Sites');

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileSitesPhp($php);
    }
    return (string)$php;
  }

  public function compileBaseUrls() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: Base Urls');
    $php->addToHeader('use clever_systems\mmm_runtime\Runtime;');
    $php->addToHeader("\$host = rtrim(\$_SERVER['HTTP_HOST'], '.');");

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileBaseUrls($php);
    }

    $php->addToFooter("error_log('MMM Unknown host or site Id: ' . \$host . ' / ' . Runtime::getEnvironment()->getLocalSiteId());");
    return (string)$php;
  }

  public function compileDbCredentials() {
    $php = new PhpFile();
    $php->addToHeader('// MMM autogenerated: DB Credentials');
    $php->addToHeader('use clever_systems\mmm_runtime\Runtime;');

    foreach ($this->project->getInstallations() as $installation) {
      $installation->compileDbCredentials($php);
    }
    return (string)$php;
  }

}