<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpAssignment implements IPhpStatement {
  /** @var string */
  protected $variable;
  /** @var mixed */
  protected $value;
  /** @var int */
  protected $indentation;

  /**
   * PhpAssignment constructor.
   * @param string $variable
   * @param mixed $value
   * @param int $indentation
   */
  public function __construct($variable, $value = NULL, $indentation = 0) {
    $this->variable = $variable;
    $this->value = $value;
    $this->indentation = $indentation;
  }

  /**
   * @return string
   */
  public function getVariable() {
    return $this->variable;
  }

  /**
   * @param string $variable
   */
  public function setVariable($variable) {
    $this->variable = $variable;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * @param mixed $value
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * @return int
   */
  public function getIndentation() {
    return $this->indentation;
  }

  /**
   * @param int $indentation
   */
  public function setIndentation($indentation) {
    $this->indentation = $indentation;
  }

  public function __toString() {
    return str_repeat(' ', $this->indentation)
      . $this->variable
      . ' = '
      . var_export($this->value, TRUE);
  }
}
