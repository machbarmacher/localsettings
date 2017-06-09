<?php

namespace machbarmacher\localsettings\RenderPhp;

use Drupal\Component\PhpStorage\PhpStorageInterface;

class PhpIf implements PhpStatementInterface {
  /** @var PhpExpressionInterface|null */
  protected $condition;
  /** @var PhpStatementsInterface|null */
  protected $then;
  /** @var PhpStatementsInterface|null */
  protected $else;

  /**
   * PhpIf constructor.
   * @param PhpExpressionInterface $condition
   * @param PhpStatementsInterface $then
   * @param PhpStatementsInterface $else
   */
  public function __construct(PhpExpressionInterface $condition = NULL, PhpStatementsInterface $then = NULL, PhpStatementsInterface $else = NULL) {
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
