<?php

if (!isset($_SERVER['SYMFONY']))
{
  $_SERVER['SYMFONY'] = '/usr/local/lib/symfony/RELEASE_1_4_6/lib';
  // throw new RuntimeException('Could not find symfony core libraries.');
}

require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->setPlugins(array(
      'sfThemeGeneratorPlugin',
      'sfDoctrinePlugin',
    ));
    
    $this->setPluginPath('sfThemeGeneratorPlugin', dirname(__FILE__).'/../../../..');
  }
}
