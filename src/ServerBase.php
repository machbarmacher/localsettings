<?php
/**
 * @file ServerBase.php
 */

namespace clever_systems\mmm_builder;


abstract class ServerBase implements ServerInterface {
  public function normalizeDocroot($docroot) {
    if (substr($docroot, 0, 1) === '~') {
      $docroot = $this->getUserHome() . '/' . substr($docroot, 1);
    }
    if (substr($docroot, 0, 1) !== '/') {
      $docroot = $this->getWebHome() . '/' . $docroot;
    }
    return $docroot;
  }

  public function getUserHome() {
    return '/home/' . $this->getUser();
  }

  public function getWebHome() {
    return $this->getUserHome();
  }

  public function getHostForSiteId() {
    return $this->getHost();
  }


}
