<?php

namespace machbarmacher\localsettings\RenderPhp;

class PhpConcat implements IStringExpression {
  /** @var IStringExpression[] */
  protected $parts = [];

  /**
   * PhpConcat constructor.
   * @param \machbarmacher\localsettings\RenderPhp\IStringExpression[] $parts
   */
  public function __construct(...$parts) {
    $this->parts = $parts;
  }

  public function add(IStringExpression $part) {
    $this->parts[] = $part;
  }

  public function __toString() {
    return $this->combine()->__toString();
  }

  public function getString() {
    return $this->combine()->getString();
  }

  public function isDoubleQuoted() {
    return $this->combine() instanceof PhpDoubleQuotedString;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\IStringLiteral[]
   */
  private function flattenedParts() {
    $flattenedParts = [];
    foreach ($this->parts as $part) {
      if ($part instanceof IStringLiteral) {
        $flattenedParts[] = $part;
      }
      elseif ($part instanceof PhpConcat) {
        $flattenedParts = array_merge($flattenedParts, $part->flattenedParts());
      }
      else {
        assert(False);
      }
    }
    return $flattenedParts;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\IStringLiteral|\machbarmacher\localsettings\RenderPhp\PhpDoubleQuotedString|\machbarmacher\localsettings\RenderPhp\PhpSingleQuotedString|mixed
   */
  protected function combine() {
    $parts = $this->flattenedParts();
    if (!$parts) {
      $result = new PhpSingleQuotedString('');
    }
    else {
      $result = array_shift($parts);
      foreach ($parts as $part) {
        if ($result instanceof PhpSingleQuotedString && $part instanceof PhpSingleQuotedString) {
          $result = new PhpSingleQuotedString($result->getString() . $part->getString());
        }
        else {
          if ($result instanceof PhpSingleQuotedString) {
            $result = $result->makeDoubleQuoted();
          }
          if ($part instanceof PhpSingleQuotedString) {
            $part = $part->makeDoubleQuoted();
          }
          $result = new PhpDoubleQuotedString($result->getString() . $part->getString());
        }
      }
    }
    return $result;
  }

}
