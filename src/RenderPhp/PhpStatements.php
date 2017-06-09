<?php


namespace machbarmacher\localsettings\RenderPhp;

class PhpStatements implements PhpStatementsInterface {
  /** @var PhpStatementInterface[] */
  protected $statements = [];

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpStatementInterface $statement
   * @return $this
   */
  public function addStatement(PhpStatementInterface $statement) {
    $this->statements[] = $statement;
    return $this;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\PhpStatementInterface[]
   */
  public function getStatements() {
    return $this->statements;
  }

  /**
   * @return string
   */
  public function __toString() {
    return implode("\n", $this->statements);
  }

  public function isEmpty() {
    return !$this->statements;
  }
}
