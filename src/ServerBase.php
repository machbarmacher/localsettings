<?php
/**
 * @file ServerBase.php
 */

namespace clever_systems\mmm_builder;


abstract class ServerBase implements ServerInterface {
  public function normalizeDocroot($docroot) {
    if (substr($docroot, 0, 1) !== '/') {
      $docroot = $this->getUserHome() . '/' . $docroot;
    }
    return $docroot;
  }

  public function getUserHome() {
    return '/home/' . $this->getUser();
  }

  public function getShortHostName() {
    return $this->getHost();
  }

  public function getLocalHostId() {
    return $this->getUser() . '@' . $this->getShortHostName();
  }

}
