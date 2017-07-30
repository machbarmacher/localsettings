<?php


namespace machbarmacher\localsettings\RenderPhp;

class PhpStatements implements IPhpStatements {
  /** @var IPhpStatement[] */
  protected $statements = [];

  /**
   * PhpComposite constructor.
   */
  public function __construct() {
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\IPhpStatement $statement
   * @return $this
   */
  public function addStatement(IPhpStatement $statement) {
    $this->statements[] = $statement;
    return $this;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\IPhpStatement[]
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
