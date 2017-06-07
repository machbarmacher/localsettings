<?php


namespace machbarmacher\localsettings\Commands;


interface CommandInterface {
  /**
   * @param array $results
   * @param bool $simulate
   */
  public function execute(array &$results, $simulate = FALSE);
}
