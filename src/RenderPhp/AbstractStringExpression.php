<?php

namespace machbarmacher\localsettings\RenderPhp;

abstract class AbstractStringExpression implements IStringExpression {

  /** @var string */
  protected $string;

  /**
   * PhpStringExpression constructor.
   * @param string $string
   */
  public function __construct($string) {
    $this->string = $string;
  }

  /**
   * @return string
   */
  public function getString() {
    return $this->string;
  }

  /**
   * @param $replacements
   * @return mixed
   */
  protected function replacedString($replacements) {
    return str_replace(array_keys($replacements), $replacements, $this->string);
  }

}
