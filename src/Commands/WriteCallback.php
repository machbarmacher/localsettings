<?php


namespace clever_systems\mmm_builder\Commands;


class WriteCallback extends Write implements CommandInterface {
  /** @var callable */
  protected $callback;

  public function __construct($filename, $callback) {
    parent::__construct($filename);
    $this->callback= $callback;
  }

  protected function getContent() {
    $callback = $this->callback;
    return $callback();
  }

}
