<?php


namespace clever_systems\mmm_builder\Commands;


abstract class FileOp implements CommandInterface {
  /** @var string */
  protected $filename;

  /**
   * WriteString constructor.
   * 
   * @param string $filename
   */
  public function __construct($filename) {
    $this->filename = $filename;
  }


  public function execute(array &$results) {
    drush_mkdir(dirname($this->filename));
    $this->doExecute();
  }

  public function simulate(array &$results) {
    $results[$this->filename] = $this->getContent();
  }

  abstract protected function getContent();

  abstract protected function doExecute();
}
