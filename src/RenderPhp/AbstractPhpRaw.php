<?php

namespace machbarmacher\localsettings\RenderPhp;

abstract class AbstractPhpRaw implements IPhpRenderable {
  /** @var string */
  protected $string;

  /**
   * RawStatement constructor.
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
   * @param string $string
   */
  public function setString($string) {
    $this->string = $string;
  }

  public function __toString() {
    return $this->string;
  }
}
