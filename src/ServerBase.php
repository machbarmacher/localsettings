<?php
/**
 * @file ServerBase.php
 */

namespace clever_systems\mmm2;


abstract class ServerBase implements ServerInterface {
  public function normalizeDocroot($docroot) {
    return $this->getUserHome() . '/' . $docroot;
  }

  public function getUserHome() {
    return '/home/' . $this->getUser();
  }

}
