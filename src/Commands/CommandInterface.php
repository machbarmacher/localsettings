<?php


namespace clever_systems\mmm_builder\Commands;


interface CommandInterface {
  /**
   * @param array $results
   * @param bool $simulate
   */
  public function execute(array &$results, $simulate = FALSE);
}
