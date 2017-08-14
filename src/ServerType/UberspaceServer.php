<?php
/**
 * @file Uberspace.php
 */

namespace machbarmacher\localsettings\ServerType;


use machbarmacher\localsettings\Installation;
use machbarmacher\localsettings\Project;
use machbarmacher\localsettings\RenderPhp\PhpFile;
use machbarmacher\localsettings\ServerBase;
use machbarmacher\localsettings\IServer;

class UberspaceServer extends ServerBase implements IServer {
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

  public function getTypeName() {
    return 'uberspace';
  }

  public function getShortHostName() {
    // User is unique.
    return $this->getTypeName();
  }

  public function getWebHome() {
    return '/var/www/virtual/' . $this->user;
  }

  public function alterHtaccess($content) {
    $content = preg_replace('#\s*Options\s+\+FollowSymLinks\s*\n#ui', 'Options +SymLinksIfOwnerMatch\n', $content);
    return <<<EOD
# START Uberspace
RewriteBase /
Options +SymLinksIfOwnerMatch
# END Uberspace


EOD
    . $content;
  }

  public function addServerSpecificSettings(PhpFile $php, Project $project) {
    parent::addServerSpecificSettings($php, $project);
    $is_d7 = $project->isD7();
    $user = $this->getUser();
    $host = $is_d7 ? 'localhost' : '127.0.0.1';
    $port = $is_d7 ? 3306 : 3307;
    $mysql_ini_file = $is_d7 ? '.my.cnf' : '.my.mariadb.cnf';
    $php->addRawStatement(<<<EOD
\$databases['default']['default'] =  array_filter(\$databases['default']['default']) + [
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

  public function alterAlias(array &$alias) {
    parent::alterAlias($alias);
    $alias['cname'] = $this->host;
  }

}
