<?php

class Sidane_Threadmarks_Listener
{
  public static function load_class($class, array &$extend)
  {
    $extend[] = 'Sidane_Threadmarks_'. $class;
  }
}
