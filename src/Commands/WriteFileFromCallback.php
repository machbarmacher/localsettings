<?php


namespace clever_systems\mmm_builder\Commands;


class WriteFileFromCallback extends AbstractWriteFile implements CommandInterface {
  /** @var callable */
  protected $callback;

  public function __construct($filename, $callback) {
    parent::__construct($filename);
    $this->callback= $callback;
  }

  protected function getContent() {
    $result = call_user_func($this->callback);
    return $result;
  }

}
