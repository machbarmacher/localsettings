<?php


namespace clever_systems\mmm_builder\Commands;


interface CommandInterface {
  public function execute(array &$results);
  public function simulate(array &$results);
}
