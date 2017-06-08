<?php
/**
 * @file Uberspace.php
 */

namespace machbarmacher\localsettings\ServerType;


use machbarmacher\localsettings\Installation;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\ServerBase;
use machbarmacher\localsettings\ServerInterface;

class UberspaceServer extends ServerBase implements ServerInterface {
  /** @var string */
  protected $host;
  /** @var string */
  protected $user;

  /**
   * Uberspace constructor.
   * @param string $host
   * @param string $user
   */
  public function __construct($host, $user) {
    $this->host = $host . '.uberspace.de';
    $this->user = $user;
  }

  public function getDefaultDocroot() {
    return 'html';
  }

  /**
   * @return string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * @return string
   */
  public function getUser() {
    return $this->user;
  }

  public function getShortHostName() {
    // User is unique.
    return 'uberspace';
  }

  public function getWebHome() {
    return '/var/www/virtual/' . $this->user;
  }

  public function alterHtaccess($content) {
    $content = preg_replace('#Options +FollowSymLinks\n#u', 'Options +SymLinksIfOwnerMatch\n', $content);
    return <<<EOD
# START Uberspace
RewriteBase /
Options +SymLinksIfOwnerMatch
# END Uberspace


EOD
    . $content;
  }

  public function addSettings(PhpFile $php, Installation $installation) {
    parent::addSettings($php, $installation);
    $is_d7 = $installation->getProject()->isD7();
    $user = $this->getUser();
    $host = $is_d7 ? 'localhost' : '127.0.0.1';
    $port = $is_d7 ? 3306 : 3307;
    $mysql_ini_file = $is_d7 ? '/.my.cnf' : '/.my.mariadb.cnf';
    $php->addToBody(<<<EOD
\$databases['default']['default'] += [
  'driver' => 'mysql',
  'username' => '$user',
  'password' => parse_ini_string(
    preg_replace('/ *#.*$/mu', '', 
    file_get_contents('/home/$user/$mysql_ini_file')
    ), TRUE, INI_SCANNER_RAW)['client']['password'],
  'host' => '$host',
  'port' => '$port',
  'prefix' => '',
];
EOD
    );
  }

}
