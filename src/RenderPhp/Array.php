<?php

namespace machbarmacher\localsettings\RenderPhp;

use machbarmacher\localsettings\RenderPhp\KeyValue;

class PhpArray implements IExpression {
  /** @var KeyValue[] */
  protected $items;
  /** @var int */
  protected $autokey = 0;
  /** @var bool */
  protected $multiline = TRUE;

  public function addItem(IExpression $value, IExpression $key = NULL) {
    // Do some magick to omit consecutive integer keys.
    if ($key instanceof LiteralValue) {
      $key_value = $key->getValue();
      // Check if key is next_int or its string representation.
      if (is_numeric($key_value) && $key_value == $this->autokey) {
        $key = NULL;
        $this->autokey += 1;
      }
    }
    $this->items[] = new KeyValue($value, $key);
  }

  /**
   * @param mixed $value
   * @param mixed $key
   */
  public function addLiteralItem($value, $key) {
    $this->addItem(new LiteralValue($value), new LiteralValue($key));
  }

  public static function fromLiteral($array) {
    $php_array = new static();
    foreach ($array as $key => $value) {
      $php_array->addLiteralItem($value, $key);
    }
    return $php_array;
  }

  public function __toString() {
    $results = [];
    foreach ($this->items as $item) {
      $results[] = (string) $item;
    }
    $glue = $this->multiline ? ",\n" : ', ';
    $items = implode($glue, array_merge($results));
    $glue = $this->multiline ? "\n" : '';
    $items = implode($glue, ['[', $items, ']']);
    return $items;
  }

  /**
   * @param bool $multiline
   */
  public function setMultiline($multiline) {
    $this->multiline = $multiline;
  }

}
