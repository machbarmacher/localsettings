<?php


namespace clever_systems\mmm_builder\Commands;


class SymlinkString extends Symlink implements CommandInterface {
  /** @var string */
  protected $string;

  public function __construct($filename, $string) {
    parent::__construct($filename);
    $this->string = $string;
  }

  protected function getContent() {
    return $this->string;
  }

}
