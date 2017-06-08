<?php


namespace machbarmacher\localsettings\RenderPhp;


class PhpFile implements PhpCodeInterface {
  /** @var PhpLines */
  protected $header;
  /** @var PhpLines */
  protected $body;
  /** @var PhpLines */
  protected $footer;

  /**
   * PhpFile constructor.
   */
  public function __construct() {
    $this->header = new PhpLines();
    $this->body = new PhpLines();
    $this->footer = new PhpLines();
  }

  public function empty() {
    return $this->header->empty() && $this->body->empty() && $this->footer->empty();
  }

  /**
   * @param string|PhpCodeInterface $line
   * @return $this
   */
  public function addToHeader($line) {
    $this->header->addLine($line);
    return $this;
  }

  /**
   * @param string|PhpCodeInterface $line
   * @return $this
   */
  public function addToBody($line) {
    $this->body->addLine($line);
    return $this;
  }

  /**
   * @param string|PhpCodeInterface $line
   * @return $this
   */
  public function addToFooter($line) {
    $this->footer->addLine($line);
    return $this;
  }

  /**
   * @return string
   */
  public function __toString() {
    return implode("\n", ["<?php", $this->header, $this->body, $this->footer, '']);
  }

}
