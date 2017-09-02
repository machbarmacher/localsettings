<?php


namespace machbarmacher\localsettings\Commands;


class AlterFile extends AbstractTwoFileOp implements CommandInterface {
  /** @var Callable */
  protected $callback;

  public function __construct($source, $target, $callback) {
    parent::__construct($source, $target);
    $this->callback = $callback;
  }

  public function execute(array &$results = [], $simulate = FALSE) {
    $source_contents = file_get_contents($this->source);
    $target_contents = call_user_func($this->callback, $source_contents);
    if (!$simulate) {
      file_put_contents($this->target, $target_contents);
    }
    $results[$this->target] = $target_contents;
  }
  
}
