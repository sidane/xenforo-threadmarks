<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class Sidane_Threadmarks_Globals
{
  public static $threadmarkLabel = null;

  private function __construct() {}
}
