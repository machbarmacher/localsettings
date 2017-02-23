<?php


namespace clever_systems\mmm_builder\Commands;


class CopyFile implements CommandInterface {
  /** @var string */
  protected $source;
  /** @var string */
  protected $target;

  use FileExistsTrait;

  /**
   * CopyFile constructor.
   * @param string $source
   * @param string $target
   */
  public function __construct($source, $target) {
    $this->source = $source;
    $this->target = $target;
  }

  public function execute(array &$results, $simulate = FALSE) {
    if (!$simulate) {
      copy($this->source, $this->target);
    }
    $results[$this->target] = file_get_contents($this->source);
  }
  
}
