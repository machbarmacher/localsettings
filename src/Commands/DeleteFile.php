<?php


namespace machbarmacher\localsettings\Commands;


class DeleteFile implements CommandInterface {
  /** @var string */
  protected $filename;

  /**
   * Delete constructor.
   * @param string $filename
   */
  public function __construct($filename) {
    $this->filename = $filename;
  }

  use FileExistsTrait;

  public function execute(array &$results = [], $simulate = FALSE) {

    if (!$simulate) {
      if ($this->checkSourceDoesExist($this->filename)) {
        unlink($this->filename);
      }
    }
    $results[$this->filename] = '<DELETED>';
  }

}
