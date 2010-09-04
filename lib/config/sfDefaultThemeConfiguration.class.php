<?php

class sfSlimThemeConfiguration extends sfModuleThemeConfiguration
{
  public function filesToCopy()
  {
    return array_merge(array(
      'lib/configuration.php'  => '%module_dir%/%module%GeneratorConfiguration.class.php',
      'lib/helper.php'         => '%module_dir%/%module%GeneratorHelper.class.php',
      'templates/_flashes.php' => '%app_dir%/templates/_flashes.php',
    ), parent::filesToCopy());
  }
}