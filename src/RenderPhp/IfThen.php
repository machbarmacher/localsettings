<?php

namespace machbarmacher\localsettings\RenderPhp;

use Drupal\Component\PhpStorage\PhpStorageInterface;

class IfThen implements IStatement {
  /** @var IExpression|null */
  protected $condition;
  /** @var IStatements|null */
  protected $then;
  /** @var IStatements|null */
  protected $else;

  /**
   * PhpIf constructor.
   * @param IExpression $condition
   * @param IStatements $then
   * @param IStatements $else
   */
  public function __construct(IExpression $condition = NULL, IStatements $then = NULL, IStatements $else = NULL) {
    $this->condition = $condition;
    $this->then = $then;
    $this->else = $else;
  }

  public function __toString() {
    $string = <<<EOD
if ($this->condition) {
$this->then
}
EOD;
    if ($this->else) {
      $string .= <<<EOD
else {
$this->else
}
EOD;
    }
    return $string;
  }
}
