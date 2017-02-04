<?php
/**
 * @file ServerInterface.php
 */

namespace clever_systems\mmm_builder;


interface ServerInterface {
  public function getUser();
  public function getHost();
  public function getShortHostName();
  public function getDefaultDocroot();
  public function normalizeDocroot($docroot);
  public function getUserHome();
}
