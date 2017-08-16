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
   * @param $preg_replacements
   * @return mixed
   */
  protected function replacedString($preg_replacements) {
    return preg_replace(array_keys($preg_replacements), $preg_replacements, $this->string);
  }

}
