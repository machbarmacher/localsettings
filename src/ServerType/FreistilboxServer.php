<?php
/**
 * @file Freistilbox.php
 */

namespace machbarmacher\localsettings\ServerType;


use machbarmacher\localsettings\Installation;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\ServerBase;
use machbarmacher\localsettings\ServerInterface;

/**
 * Class Freistilbox
 * @package machbarmacher\localsettings\ServerType
 *
 * @todo Implement repository branch & url & environment
 * repository url = ssh://{{site-handle}}@repo.freistilbox.net/~/site
 */
class FreistilboxServer extends ServerBase implements ServerInterface {
  /** @var string */
  protected $cluster;
  /** @var string */
  protected $site_handle;

  /**
   * Freistilbox constructor.
   * @param string $cluster
   * @param string $site_handle
   */
  public function __construct($cluster, $site_handle) {
    $this->cluster = $cluster;
    $this->site_handle = $site_handle;
  }

  public function getDefaultDocroot() {
    return 'current/docroot';
  }

  public function getHost() {
    return $this->cluster . 's.freistilbox.net';
  }

  public function getUser() {
    return $this->site_handle;
  }

  public function getUserHome() {
    return '/srv/www/freistilbox/home/' . $this->site_handle;
  }

  public function getWebHome() {
    return '/srv/www/freistilbox/home/' . $this->site_handle;
  }

  public function getShortHostName() {
    // User is unique and we can't access host on runtime.
    return 'freistilbox';
  }

  public function getUniqueInstallationName(Installation $installation) {
    // Highlander: There's only one.
    return $this->getUniqueAccountName();
  }

  public function alterHtaccess($content) {
    return <<<EOD
# START Freistilbox
RewriteBase /
Options +FollowSymLinks
# END Freistilbox


EOD
      . $content;
  }

  public function addServerSettings(PhpFile $php, Installation $installation) {
    parent::addSettings($php, $installation);
    $is_d7 = $installation->getProject()->isD7();
    $settings_variable = $installation->getProject()->getSettingsVariable();
    $drupal_major_version = $installation->getProject()->getDrupalMajorVersion();

    // REDIS:
    // Get unique redis credentials.
    // We can neither rely on module_exists here (it's too early) nor use
    // class_exists (as cache classes are included via $conf['cache_backends'])
    // nor check that setting (as this runs before user settings).
    // So we just always include the config snippet.
    // Also we don NOT want the snippet to set $settings['cache']['default']
    // and $settings['cache_prefix']['default'] so we filter the include.
    $redis_relevant_keys = $is_d7 ?
      ['redis_client_host', 'redis_client_port', 'redis_client_password'] :
      ['redis.connection'];

    $php->addToBody(<<<EOD
require "../config/drupal/settings-d{$drupal_major_version}-site.php";
// Redis
if (
  (\$redis_configs = glob("../config/drupal/settings-d{$drupal_major_version}-redis*.php"))
  && count(\$redis_configs) == 1
) {
  \$importer = function(\$file) { include(\$file); return {$settings_variable};};
  {$settings_variable} = array_intersect_key(\$importer(\$redis_configs[0]), array_flip($redis_relevant_keys)) 
    + {$settings_variable};
}
// DATABASE:
if (
  // Explicit database ID.
  !empty(\$databases['default']['default']['database'])
  && (\$database = \$databases['default']['default']['database'])
) {
  require_once "../config/drupal/settings-d{$drupal_major_version}-\$database.php";
}
elseif (
  // Unique database ID.
  (\$database_configs = glob("../config/drupal/settings-d{$drupal_major_version}-db*.php"))
  && count(\$database_configs) == 1
) {
  require_once \$database_configs[0];
}
EOD
    );

  }

}
