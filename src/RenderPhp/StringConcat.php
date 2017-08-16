<?php

namespace machbarmacher\localsettings\RenderPhp;

class StringConcat implements IStringExpression {
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
    return $this->combine() instanceof StringDoubleQuoted;
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
      elseif ($part instanceof StringConcat) {
        $flattenedParts = array_merge($flattenedParts, $part->flattenedParts());
      }
      else {
        assert(False);
      }
    }
    return $flattenedParts;
  }

  /**
   * @return \machbarmacher\localsettings\RenderPhp\IStringLiteral|\machbarmacher\localsettings\RenderPhp\StringDoubleQuoted|\machbarmacher\localsettings\RenderPhp\StringSingleQuoted|mixed
   */
  protected function combine() {
    $parts = $this->flattenedParts();
    if (!$parts) {
      $result = new StringSingleQuoted('');
    }
    else {
      $result = array_shift($parts);
      foreach ($parts as $part) {
        if ($result instanceof StringSingleQuoted && $part instanceof StringSingleQuoted) {
          $result = new StringSingleQuoted($result->getString() . $part->getString());
        }
        else {
          if ($result instanceof StringSingleQuoted) {
            $result = $result->makeDoubleQuoted();
          }
          if ($part instanceof StringSingleQuoted) {
            $part = $part->makeDoubleQuoted();
          }
          $result = new StringDoubleQuoted($result->getString() . $part->getString());
        }
      }
    }
    return $result;
  }

}
