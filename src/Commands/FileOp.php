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


  public function execute(array &$results, $simulate = FALSE) {
    $results[$this->filename] = $this->getContent();
    if (!$simulate) {
      drush_mkdir(dirname($this->filename));
      $this->doExecute();
    }
  }

  abstract protected function getContent();

  abstract protected function doExecute();
}
