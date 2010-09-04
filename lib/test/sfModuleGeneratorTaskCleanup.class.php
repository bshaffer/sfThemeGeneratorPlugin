<?php

class sfModuleGeneratorTaskCleanup
{
  protected $modules;

  public function __construct()
  {
    $this->modules = $this->getModules();
  }

  public function __destruct()
  {
    $this->cleanup();
  }

  public function cleanup()
  {
    // Clear any added modules since the construction of this class
    foreach (array_diff($this->getModules(), $this->modules) as $dir)
    {
      sfToolkit::clearDirectory($dir);
      rmdir($dir);
    }

    // Clear routing.yml file  
    file_put_contents(dirname(__FILE__).'/../../test/fixtures/project/apps/frontend/config/routing.yml', '');
  }

  protected function getModules()
  {
    return sfFinder::type('dir')->maxdepth(0)
      ->in(dirname(__FILE__).'/../../test/fixtures/project/apps/frontend/modules');
  }
}