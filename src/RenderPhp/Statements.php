<?php


namespace machbarmacher\localsettings\RenderPhp;

class Statements implements IStatements {
  /** @var IStatement[] */
  protected $statements = [];

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\IStatement $statement
   * @return $this
   */
  public function addStatement(IStatement $statement) {
    $this->statements[] = $statement;
    return $this;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\IStatement[]
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
