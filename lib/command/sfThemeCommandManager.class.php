<?php

/**
* Class to pass options to the admin theme configuration
*/
class sfThemeCommandManager extends sfCommandManager
{
  protected
    $commandManager,
    $options;

  public function __construct(sfCommandManager $commandManager)
  {
    $this->commandManager = $commandManager;
    $this->argumentSet    = $commandManager->argumentSet;
    $this->optionSet    = $commandManager->optionSet;
  }
  
  /**
   * Parses a long option.
   *
   * @param string $argument The option argument
   */
  protected function parseLongOption($argument)
  {
    if (false !== strpos($argument, '='))
    {
      list($name, $value) = explode('=', $argument, 2);
    }
    else
    {
      $name = $argument;
      $value = true;
    }
    
    if (!$this->optionSet->hasOption($name))
    {
      $option = new sfCommandOption($name, null, sfCommandOption::PARAMETER_OPTIONAL);
    }
    else
    {
      $option = $this->optionSet->getOption($name); 
    }

    $this->setOption($option, $value);
  }
}
