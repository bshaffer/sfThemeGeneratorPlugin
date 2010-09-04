<?php

class sfDefaultThemeConfiguration extends sfModuleThemeConfiguration
{
  public function filesToCopy()
  {
    return array_merge(array(
      'templates/custom.php' => '%app_dir%/templates/custom.php',
    ), parent::filesToCopy());
  }
}