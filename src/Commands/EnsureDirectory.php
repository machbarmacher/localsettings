<?php


namespace clever_systems\mmm_builder\Commands;


class EnsureDirectory implements CommandInterface {
  /** @var string */
  protected $dirname;

  /**
   * WriteString constructor.
   *
   * @param string $dirname
   */
  public function __construct($dirname) {
    $this->dirname = $dirname;
  }


  public function execute(array &$results, $simulate = FALSE) {
    $results[$this->dirname] = '<DIR>';
    if (!$simulate) {
      // Assure directory is there.
      drush_mkdir($this->dirname);
    }
  }
}
