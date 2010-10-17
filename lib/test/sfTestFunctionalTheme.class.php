<?php

/**
* 
*/
class sfTestFunctionalTheme extends sfTestFunctional
{  
  protected
    $cleanup = null;

  public function __construct(sfBrowserBase $browser, lime_test $lime = null, $testers = array())
  {
    if ($lime == null) 
    {
      $lime = new sfThemeGenerateLimeTest();
      $lime->configuration = $browser->getContext()->getConfiguration();
    }
    
    $this->cleanup = new sfThemeGenerateTaskCleanup();
    $this->cleanup();

    parent::__construct($browser, $lime, $testers);
  }
  
  public function cleanup()
  {
    $this->cleanup->cleanup();
    
    return $this;
  }
  
  public function runTask($class, $arguments = array(), $options = array(), $boolean = true, $message = null)
  {
    $this->test()->task_ok($class, $arguments, $options, $boolean, $message);
    
    sfToolkit::clearDirectory(sfConfig::get('sf_cache_dir'));
    
    return $this;
  }
  
  public function isModuleAction($module, $action, $statusCode = 200)
  {
    $this->with('request')->begin()->
  	  isParameter('module', $module)->
  	  isParameter('action', $action)->
  	end()->  

    with('response')->begin()->
    	isStatusCode($statusCode)->
    end();

    return $this;
  }
}
