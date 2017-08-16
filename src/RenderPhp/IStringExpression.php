<?php


namespace machbarmacher\localsettings\RenderPhp;


interface IStringExpression extends IStringLiteral {
  public function getString();
  public function isDoubleQuoted();
}
