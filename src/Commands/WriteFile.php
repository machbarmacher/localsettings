<?php


namespace machbarmacher\localsettings\Commands;


class WriteFile extends AbstractWriteFile implements CommandInterface {
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
