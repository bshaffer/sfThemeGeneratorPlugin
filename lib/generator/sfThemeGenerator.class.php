<?php

class sfThemeGenerator extends sfDoctrineGenerator
{
  protected
    $options = array(),
    $availableConfigs = array();

  public function get($config, $default = null)
  {
    if (isset($this->options[$config])) {
      return $this->options[$config];
    }

    return $default;
  }

  // Render text in HTML
  public function renderHtmlText($text)
  {
    if ($this->get('i18n')) {
      $text = sprintf('<?php echo __(\'%s\', array(), \''. $this->getI18nCatalogue().'\') ?>', $this->escapeString($text));
      return $this->replaceTokens($text, 'php');
    }

    return $this->replaceTokens($text, 'html');
  }

  // Render text in a PHP block
  public function renderPhpText($text)
  {
    $text = $this->asPhp($this->replaceTokens($text, 'php'));
    
    if ($this->get('i18n')) {
      $text = sprintf('__(%s, array(), \''. $this->getI18nCatalogue().'\')', $text);
    }
    
    return $text;
  }

  // Render text that will appear in a php array
  public function renderPhpArrayText($text)
  {
    if ($this->get('i18n')) {
      $text = sprintf('||__(\'%s\', array(), \''. $this->getI18nCatalogue().'\')||', $text);
    }
    
    return $text;
  }

  public function replaceTokens($string, $format = 'html')
  {
    $tr1 = array();
    $tr2 = array();
    $renderTextAsBlock = false;
    
    preg_match_all('/\'\|\|(.*?)\|\|\'/', $string, $matches, PREG_PATTERN_ORDER);

    if (count($matches[1])) {
      foreach ($matches[1] as $i => $name)
      {
        $tr1[$matches[0][$i]] = $this->unescapeString($name);
      }
    }
    
    preg_match_all('/%%([^%]+)%%/', $string, $matches, PREG_PATTERN_ORDER);
    
    if (count($matches[1])) {
      $renderTextAsBlock = false;
    
      foreach ($matches[1] as $i => $name)
      {
        if ($value = $this->get($name)) {
          $tr2[$matches[0][$i]] = $value;
        }
        else {
          $renderTextAsBlock = true;
          $getter  = $name == 'to_string' ? '$'.$this->getSingularName() : $this->getColumnGetter($name, true);
          $tr2[$matches[0][$i]]  = sprintf("'.%s.'", $getter);
        }
      }
    }

    if ($renderTextAsBlock) {
      switch ($format) {
        case 'html':
          $string = $this->renderTextInPhpBlock($this->escapeString($string));
          break;

        case 'php':
          break;
      }
    }
    
    if ($tr1) {
      $string = strtr($string, $tr1);
    }
    
    if ($tr2) {
      $string = strtr($string, $tr2);
    }
    return $this->clearEmptyStrings($string);
  }

  public function renderTextInPhpBlock($text)
  {
    if (strpos($text, '<?php') !== 0) {
      $text = sprintf('<?php echo \'%s\' ?>', $text);
    }

    return $text;
  }

  public function clearEmptyStrings($text)
  {
    if (strpos($text, "''.") === 0) {
      $text = substr($text, 3);
    }
    
    if (strpos(strrev($text), "''.") === 0) {
      $text = strrev(substr(strrev($text), 3));
    }
    
    return strtr($text, array(
      ".''." => '.',
      "(''." => '(',
      ", ''." => ', ',
      ".'')" => ')',
      ".''," => ',',
    ));
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

  public function linkTo($action, $params)
  {
    $action = strpos($action, '_') === 0 ? substr($action, 1) : $action;

    $forObject = isset($params['object_link']) && $params['object_link'];

    $params = array_merge(array('class' => $action), $params);

    $method = sprintf('linkTo%s', ucwords(sfInflector::camelize($action)));

    $link = method_exists($this, $method) ? $this->$method($params) : $this->getLinkToAction($action, $params, $forObject);

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
    $label  = isset($params['label']) ? $params['label'] : sfInflector::humanize($actionName);
    if (isset($params['confirm'])) {
      $params['confirm'] = $this->renderPhpArrayText($params['confirm']);
    }

    if (isset($params['title'])) {
      $params['title'] = $this->renderPhpArrayText($params['title']);
    }

    // Not a "link_to" attribute
    unset($params['action'], $params['label'], $params['object_link']);

    $url_params = $pk_link ? '?'.$this->getPrimaryKeyUrlParams() : '\'';

    return $this->replaceTokens(sprintf('[?php echo link_to(%s, \'%s/%s, %s) ?]', $this->renderPhpText($label), $this->getModuleName(), $action.$url_params, $this->renderArray($params)), 'php');
  }

  public function renderArray($array)
  {
    return $this->asPhp($array);
  }

  public function getClassLabel()
  {
    return $this->get('class_label', $this->getModelClass());
  }

  /**
   * Loads the configuration for this generated module.
   */
  protected function loadConfiguration()
  {
    $this->configToOptions($this->config);
    $this->configToOptions($this->params);
    $this->options['singular_name'] = $this->getSingularName();
    $this->options['class_label']   = $this->getClassLabel();

    $class = $this->getConfigurationClass();
    $configuration = new $class($this->config, $this->params);
    $this->configToOptions($configuration->getConfiguration());

    return $configuration;
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

  /**
   * Generates classes and templates in cache.
   *
   * @param array $params The parameters
   *
   * @return string The data to put in configuration cache
   */
  public function generate($params = array())
  {
    $this->validateParameters($params);

    $this->modelClass = $this->params['model_class'];

    // generated module name
    $this->setModuleName($this->params['moduleName']);
    $this->setGeneratedModuleName('auto'.ucfirst($this->params['moduleName']));

    // theme exists?
    $theme = isset($this->params['theme']) ? $this->params['theme'] : 'default';
    $this->setTheme($theme);
    $themeDir = $this->generatorManager->getConfiguration()->getGeneratorTemplate($this->getGeneratorClass(), $theme, '');
    if (!is_dir($themeDir))
    {
      throw new sfConfigurationException(sprintf('The theme "%s" does not exist.', $theme));
    }

    // configure the model
    $this->configure();

    $this->configuration = $this->loadConfiguration();

    $files = $this->getPhpFilesToGenerate($themeDir);

    // generate files
    $this->generatePhpFiles($this->generatedModuleName, $files);

    // move helper file
    if (file_exists($file = $this->generatorManager->getBasePath().'/'.$this->getGeneratedModuleName().'/lib/helper.php'))
    {
      @rename($file, $this->generatorManager->getBasePath().'/'.$this->getGeneratedModuleName().'/lib/Base'.ucfirst($this->moduleName).'GeneratorHelper.class.php');
    }

    return "require_once(sfConfig::get('sf_module_cache_dir').'/".$this->generatedModuleName."/actions/actions.class.php');";
  }

  // Provides a hook to change generated files
  protected function getPhpFilesToGenerate($themeDir)
  {
    return sfFinder::type('file')->relative()->in($themeDir);
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
  
  public function unescapeString($string)
  {
    return str_replace("\\'", "'", $string);
  }

  protected function getConfigurationClass()
  {
    return 'sfThemeGeneratorConfiguration';
  }
}
