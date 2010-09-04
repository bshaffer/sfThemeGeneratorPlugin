<?php

class sfTestThemeConfiguration extends sfThemeConfiguration
{
  public function filesToCopy()
  {
    return array_merge(array(
      'templates/custom.php' => '%app_dir%/templates/custom.php',
    ), parent::filesToCopy());
  }
}