<?php

namespace machbarmacher\localsettings\RenderPhp;

abstract class AbstractPhpRaw {

  /** @var string */
  protected $string;

  /**
   * Constructor.
   * @param string $string
   */
  public function __construct($string) {
    $this->string = $string;
  }

  public function __toString() {
    return $this->string;
  }

}
