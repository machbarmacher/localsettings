<?php

namespace clever_systems\mmm_runtime;

class PatternArrayObject extends \ArrayObject {
  public function offsetExists($index) {
    return !is_null($this->offsetGet($index));
  }

  public function offsetGet($index) {
    foreach ($this->getIterator() as $pattern => $value) {
      $compiled_pattern = '/' . preg_replace('/\\\\\\*/', '[^.]+', preg_quote($pattern)) . '/';
      if (preg_match($compiled_pattern, $index)) {
        return $value;
      }
    }
  }
}
$sites = new PatternArrayObject();
