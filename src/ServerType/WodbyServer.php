<?php

namespace machbarmacher\localsettings\ServerType;

use machbarmacher\localsettings\IDeclaration;
use machbarmacher\localsettings\Project;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\ServerBase;
use machbarmacher\localsettings\Tools\Replacements;

class WodbyServer extends ServerBase {
  /** @var string */
  protected $app;
  /** @var string */
  protected $company;

  /**
   * WodbyServer constructor.
   *
   * @param string $app
   * @param string $company
   */
  public function __construct($app, $company) {
    $this->app = $app;
    $this->company = $company;
  }


  public function getUser() {
    return 'www-data';
  }

  public function getHost() {
    return "dev.$this->app.$this->company.wod.by";
  }

  public function getTypeName() {
    return 'wodby';
  }

  public function getDefaultDocroot() {
    return '/var/www/html/web';
  }

  public function getWebHome() {
    return '/var/www/html/web';
  }

  public function alterHtaccess($content) {
    // Nothing to do.
  }

  public function getShortHostName() {
    return $this->getHost();
  }

  public function getUniqueAccountName() {
    return $this->getHost();
  }

  public function getUniqueInstallationName(IDeclaration $declaration) {
    // Docroot does not matter here.
    return $this->getUniqueAccountName();
  }

  /**
   * Add server specific settings.
   *
   * We're getting into a mismatch between localsettings and wodby here.
   * Both want to rule. Workaround this by overriding localsettings vars.
   */
  public function addServerSpecificSettings(PhpFile $php, Replacements $replacements, Project $project) {
    $settings_variable = $project->getSettingsVariable();
    $settings_variable_name = substr($settings_variable, 1);

    $php->addRawStatement(<<<EOD
  \$importer = function(\$file) { include(\$file); return get_defined_vars();};
  \$vars = \$importer(\'/var/www/conf/wodby.settings.php\');

  \$databases = \$vars['databases'];
  // We want to control the cache bins ourselves.
  unset(\$vars['$settings_variable_name']['cache']);
  $settings_variable = \$vars['$settings_variable_name'] + $settings_variable;
  // Voluntarily ignore \$config_directories.
EOD
    );
    // @todo Overwrite settings if needed.
  }

  public function alterAlias(array &$alias) {
    parent::alterAlias($alias);
    // TODO: Add cname and git-deployment-uri
  }

  /**
   * Local check expression.
   *
   * Where is this used anyway?
   * - in local-check at symlinking
   * - in alias-is-local magick
   * - in globber recognition
   *
   * @return string
   */
  public function getRuntimeIsLocalCheck() {
    return "preg_match('/\\.$this->app$/u', (string)getenv('WODBY_APP_NAME'))";
  }

}
