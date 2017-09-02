<?php


namespace machbarmacher\localsettings\Commands;


interface CommandInterface {
  /**
   * @param array $results
   * @param bool $simulate
   * @return
   */
  public function execute(array &$results = [], $simulate = FALSE);
}
