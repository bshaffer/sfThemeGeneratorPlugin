<?php

class sfDefaultThemeConfiguration extends sfModuleThemeConfiguration
{
  public function filesToCopy()
  {
    return array_merge(array(
      '%module_dir%/%module%',
      'lib/configuration.php'  => '%module_dir%/%module%/lib/%module%GeneratorConfiguration.class.php',
      'lib/helper.php'         => '%module_dir%/%module%/lib/%module%GeneratorHelper.class.php',
    ), parent::filesToCopy());
  }
}