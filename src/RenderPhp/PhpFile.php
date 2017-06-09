<?php


namespace machbarmacher\localsettings\RenderPhp;

/**
 * Class PhpFile
 * @package machbarmacher\localsettings\RenderPhp
 *
 * @todo Consider using symfony AST generator or zend\code.
 */
class PhpFile {
  /** @var PhpStatements */
  protected $statements;

  /**
   * PhpFile constructor.
   */
  public function __construct() {
    $this->statements = new PhpStatements();
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\PhpStatements
   */
  public function getLines() {
    return $this->statements;
  }

  public function isEmpty() {
    return $this->statements->isEmpty();
  }

  /**
   * @param \machbarmacher\localsettings\RenderPhp\PhpStatementInterface $statement
   * @return $this
   */
  public function addStatement(PhpStatementInterface $statement) {
    $this->statements->addStatement($statement);
    return $this;
  }

  /**
   * @param string $string
   * @return $this
   */
  public function addRawStatement($string) {
    return $this->addStatement(new PhpRawStatement($string));
  }


  /**
   * @return string
   */
  public function __toString() {
    return implode("\n", ["<?php", $this->statements, '']);
  }

}
