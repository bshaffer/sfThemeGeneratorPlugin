<?php

class sfDefaultThemeConfiguration extends sfThemeConfiguration
{
  protected
    $theme = 'default';
    
  public function setup()
  {
    $this->askForApplication();

    $this->askForModel();

    $this->task->bootstrapSymfony($this->options['application'], $this->options['env']);
    
    $this->askForOption('module', null, sfInflector::underscore($this->options['model']));
  }
}