<?php

namespace machbarmacher\localsettings\Tools;

class Replacements {

  /** @var string[] */
  protected $replacements = [];

  /**
   * @param string $placeholder
   * @param string $replacement
   * @return $this
   */
  public function register($placeholder, $replacement) {
    $this->replacements[$placeholder] = $replacement;
    return $this;
  }

  /**
   * @param $string
   * @return string
   */
  public function apply($string) {
    return str_replace(array_keys($this->replacements), $this->replacements, $string);
  }
}
