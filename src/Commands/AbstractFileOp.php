<?php


namespace machbarmacher\localsettings\Commands;


abstract class AbstractFileOp implements CommandInterface {
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


  public function execute(array &$results = [], $simulate = FALSE) {
    $results[$this->filename] = $this->getContent();
    if (!$simulate) {
      $dirname = dirname($this->filename);
      // Assure directory is there.
      drush_mkdir($dirname);
      // Assure we can write.
      foreach ([$this->filename, $dirname] as $item) {
        if (file_exists($item)) {
          $permissions = fileperms($item);
          $user_can_write = 0200;
          if (!(!$permissions & $user_can_write)) {
            chmod($item, $permissions | $user_can_write);
          }
        }
      }
      $this->doExecute();
    }
  }

  abstract protected function getContent();

  abstract protected function doExecute();
}
