<?php
/**
 * @file ServerInterface.php
 */

namespace clever_systems\mmm2;


interface ServerInterface {
  public function getHost();
  public function getUser();
  public function getDefaultDocroot();
  public function normalizeDocroot($docroot);
  public function getUserHome();
}