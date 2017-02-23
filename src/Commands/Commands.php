<?php


namespace clever_systems\mmm_builder\Commands;


class Commands implements CommandInterface {
  /** @var CommandInterface[] */
  protected $commands = [];

  public function add(CommandInterface $command) {
    $this->commands[] = $command;
  }

  public function execute(array &$results, $simulate = FALSE) {
    foreach ($this->commands as $command) {
      $command->execute($results, $simulate);
    }
  }
}
