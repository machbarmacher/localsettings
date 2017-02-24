<?php


namespace clever_systems\mmm_builder\Commands;


class EnsureDirectory implements CommandInterface {
  /** @var string */
  protected $dirname;
  /** @var bool */
  protected $gitkeep;

  /**
   * WriteString constructor.
   *
   * @param string $dirname
   */
  public function __construct($dirname, $gitkeep = FALSE) {
    $this->dirname = $dirname;
    $this->gitkeep = $gitkeep;
  }


  public function execute(array &$results, $simulate = FALSE) {
    $results[$this->dirname] = '<DIR>';
    if (!$simulate) {
      // Assure directory is there.
      drush_mkdir($this->dirname);
      if ($this->gitkeep) {
        (new WriteFile("$this->dirname/.gitkeep", ''))->execute($results, $simulate);
      }
    }
  }
}
