<?php


namespace machbarmacher\localsettings\Commands;


class Symlink extends AbstractSymlink implements CommandInterface {
  /** @var string */
  protected $target;

  public function __construct($filename, $target) {
    parent::__construct($filename);
    $this->target = $target;
  }

  protected function getLinkTarget() {
    return $this->target;
  }

}
