<?php


namespace machbarmacher\localsettings\Commands;


abstract class AbstractTwoFileOp implements CommandInterface {
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

  abstract public function execute(array &$results, $simulate = FALSE);
  
}
