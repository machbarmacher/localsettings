<?php

namespace machbarmacher\localsettings\RenderPhp;

use Drupal\Component\PhpStorage\PhpStorageInterface;

class PhpIf implements IPhpStatement {
  /** @var IPhpExpression|null */
  protected $condition;
  /** @var IPhpStatements|null */
  protected $then;
  /** @var IPhpStatements|null */
  protected $else;

  /**
   * PhpIf constructor.
   * @param IPhpExpression $condition
   * @param IPhpStatements $then
   * @param IPhpStatements $else
   */
  public function __construct(IPhpExpression $condition = NULL, IPhpStatements $then = NULL, IPhpStatements $else = NULL) {
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
