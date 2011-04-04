<?php

class sfThemeGenerator extends sfDoctrineGenerator
{
  protected
    $options = array(),
    $availableConfigs = array();

  // Render text in HTML
  public function renderText($text)
  {
    return $text;
  }
  
  // Render text in a PHP block
  public function renderTextInBlock($text)
  {
    return $this->asPhp($text);
  }
  
  public function startCredentialCondition($params = array())
  {
    if (isset($params['credentials']))
    {
      return sprintf("[?php if (\$sf_user->hasCredential(%s)): ?]
", $this->asPhp($params['credentials']));
    }
  }
  
  public function endCredentialCondition($params)
  {
    if (isset($params['credentials']))
    {
      return '[?php endif; ?]';
    }
  }

  public function addCredentialCondition($content, $params = array())
  {
    if (isset($params['credentials']))
    {
      $credentials = $this->asPhp($params['credentials']);

      $content = sprintf("
[?php if (\$sf_user->hasCredential(%s)): ?]
  %s
[?php endif; ?]
", $credentials, $content);
    }
    
    return $content;
  }

  public function get($config, $default = null)
  {
    if (isset($this->options[$config])) {
      return $this->options[$config];
    }
    
    return $default;
  }
  
  public function configToOptions($configs, $prefix = '')
  {
    foreach ($configs as $name => $config) 
    {
      $name = $prefix ? $prefix.'_'.$name : $name;
      if (is_array($config)) 
      {
        $this->configToOptions($config, $name);
      }

      $this->options[$name] = $config;
    }
  }
  
  /**
   * Loads the configuration for this generated module.
   */
  protected function loadConfiguration()
  {
    $this->params['singular_name'] = $this->getSingularName();
    $this->configToOptions($this->config);
    $this->configToOptions($this->params);

    try
    {
      $this->generatorManager->getConfiguration()->getGeneratorTemplate($this->getGeneratorClass(), $this->getTheme(), '../parts/configuration.php');
    }
    catch (sfException $e)
    {
      return null;
    }

    $config = $this->getGeneratorManager()->getConfiguration();
    if (!$config instanceof sfApplicationConfiguration)
    {
      throw new LogicException('The sfModelGenerator can only operates with an application configuration.');
    }

    $basePath = $this->getGeneratedModuleName().'/lib/'.$this->getModuleName().'GeneratorConfiguration.class.php';
    $this->getGeneratorManager()->save($basePath, $this->evalTemplate('../parts/configuration.php'));

    require_once $this->getGeneratorManager()->getBasePath().'/'.$basePath;

    $class = 'Base'.ucfirst($this->getModuleName()).'GeneratorConfiguration';

    foreach ($config->getLibDirs($this->getModuleName()) as $dir)
    {
      if (!is_file($configuration = $dir.'/'.$this->getModuleName().'GeneratorConfiguration.class.php'))
      {
        continue;
      }

      require_once $configuration;
      $class = $this->getModuleName().'GeneratorConfiguration';
      break;
    }

    $configuration = new $class();
    $this->configToOptions($configuration->getConfiguration());

    return $configuration;
  }

  public function linkTo($action, $params)
  {
    $action = strpos($action, '_') === 0 ? substr($action, 1) : $action;

    $params = array_merge(array('attributes' => array('class' => $action)), $params);
        
    $method = sprintf('linkTo%s', ucwords(sfInflector::camelize($action)));

    $link = method_exists($this, $method) ? $this->$method($params) : $this->getLinkToAction($action, $params, true);
    
    return $this->addCredentialCondition($link, $params);
  }
  

  /**
   * Returns HTML code for an action link.
   *
   * @param string  $actionName The action name
   * @param array   $params     The parameters
   * @param boolean $pk_link    Whether to add a primary key link or not
   *
   * @return string HTML code
   */
  public function getLinkToAction($actionName, $params, $pk_link = false)
  {
    $action = isset($params['action']) ? $params['action'] : 'List'.sfInflector::camelize($actionName);

    $url_params = $pk_link ? '?'.$this->getPrimaryKeyUrlParams() : '\'';

    return '[?php echo link_to(\''.$params['label'].'\', \''.$this->getModuleName().'/'.$action.$url_params.', '.$this->asPhp($params['params']).') ?]';
  }
  
  public function urlFor($action, $routeName = true)
  {
    if (isset($this->params['route_prefix']))
    {
      $route = 'list' == $action ? $this->params['route_prefix'] : $this->params['route_prefix'].'_'.$action;
      return $this->asPhp(($routeName ? '@' : '').$route);
    }

    return $this->asPhp($this->getModuleName().'/'.$action);
  }
  
  public function getFormClass()
  {
    return $this->get('form_class', $this->getModelClass().'Form');
  }
  
  protected function validateParameters($params)
  {
    foreach (array('model_class', 'moduleName') as $key)
    {
      if (!isset($params[$key]))
      {
        throw new sfParseException(sprintf('sfModelGenerator must have a "%s" parameter.', $key));
      }
    }

    if (!class_exists($params['model_class']))
    {
      throw new sfInitializationException(sprintf('Unable to generate a module for non-existent model "%s".', $params['model_class']));
    }

    if (isset($params['config'])) {
      $this->checkConfigIsValid($params['config'], $this->availableConfigs);
      $this->config = $params['config'];
    }
    else {
      $this->config = array();
    }

    unset($params['config']);
    $this->params = $params;
  }
  
  public function checkConfigIsValid($configs, $available)
  {
    if ($available !== array()) // all options pass for "array()"
    {
      foreach ($configs as $key => $config) 
      {
        if (!isset($available[$key])) 
        {
          throw new InvalidArgumentException(sprintf('Configuration key "%s" is invalid.', $key));
        }
      
        if (is_array($config) && is_array($available[$key])) 
        {
          $this->checkConfigIsValid($config, $available[$key]);
        }
      }
    }
  }
}