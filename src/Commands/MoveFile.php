<?php


namespace clever_systems\mmm_builder\Commands;


class MoveFile implements CommandInterface {
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
    (new CopyFile($this->source, $this->target))->execute($results, $simulate);
    (new DeleteFile($this->source))->execute($results, $simulate);
  }
  
}
