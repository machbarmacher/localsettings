<?php
/**
 * @file Uberspace.php
 */

namespace machbarmacher\localsettings\ServerType;


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

}
