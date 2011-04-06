<?php

/**
*
*/
abstract class sfThemeGeneratorConfiguration extends sfOldAndBustedGeneratorConfiguration
{
  protected
    $options;

  /**
   * Constructor.
   */
  public function __construct($configs, $options)
  {
    $this->options = $options;
    $this->compile($configs);
  }

  public function getConfigValue($config, $default = null)
  {
    if (isset($this->configuration[$config]))
    {
      return $this->configuration[$config];
    }

    return $default;
  }

  public function getOptionValue($name, $default = null)
  {
    if (isset($this->options[$name]))
    {
      return $this->options[$name];
    }

    return $default;
  }

  public function getConfiguration()
  {
    return $this->configuration;
  }

  protected function getDefaultConfiguration()
  {
    return array();
  }
}
