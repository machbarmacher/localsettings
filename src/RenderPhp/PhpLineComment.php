<?php


namespace clever_systems\mmm_builder\RenderPhp;


class PhpLineComment implements PhpCodeInterface {
  /** @var string */
  protected $value;
  /** @var int */
  protected $indent;

  /**
   * PhpValue constructor.
   *
   * @param string $value
   * @param int $indent
   */
  public function __construct($value, $indent = 2) {
    $this->value = $value;
    $this->indent = $indent;
  }

  /**
   * @return string
   */
  public function __toString() {
    $return = '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/u", $this->value) as $line){
      $return .= str_repeat(' ', $this->indent);
      $return .= $line;
      $return .= "\n";
    }
    return $return;
  }
}
