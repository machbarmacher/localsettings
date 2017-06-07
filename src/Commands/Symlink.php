<?php


namespace machbarmacher\localsettings\Commands;


class Symlink extends AbstractSymlink implements CommandInterface {
  /** @var string */
  protected $string;

  public function __construct($filename, $string) {
    parent::__construct($filename);
    $this->string = $string;
  }

  protected function getLinkTarget() {
    return $this->string;
  }

}
