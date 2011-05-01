<?php

class sfThemeGenerator extends sfDoctrineGenerator
{
  protected
    $options = array(),
    $themeDir = null,
    $availableConfigs = array();

  public function getClassLabel()
  {
    return $this->get('class_label', $this->getModelClass());
  }

  public function getFormClass()
  {
    return $this->get('form_class', $this->getModelClass().'Form');
  }

  public function getThemeDirectory()
  {
    return $this->themeDir;
  }

  public function hasI18nEnabled()
  {
    return $this->get('i18n', false);
  }

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
    if ($this->hasI18nEnabled()) {
      $text = $this->renderPhpText($text);
      return sprintf('<?php echo %s ?>', $text);
    }

    return $this->parser->renderHtmlText($text);
  }

  // Render text in a PHP block
  public function renderPhpText($text)
  {
    $text = $this->parser->renderPhpText($text);

    if ($this->hasI18nEnabled()) {
      $text = sprintf('__(%s, array(), \''. $this->getI18nCatalogue().'\')', $text);
    }

    return $text;
  }

  // Render text that will appear in a php array
  public function renderPhpArrayText($text)
  {
    if ($this->hasI18nEnabled()) {
      $text = $this->parser->wrapPhpToken(sprintf('__(\'%s\', array(), \''. $this->getI18nCatalogue().'\')', $text));
    }

    return $text;
  }

  public function startCredentialCondition($params = array())
  {
    if (isset($params['credentials']))
    {
      return sprintf("[?php if (\$sf_user->hasCredential(%s)): ?]
", $this->renderCredentials($params['credentials']));
    }
  }

  public function endCredentialCondition($params)
  {
    if (isset($params['credentials']))
    {
      return "[?php endif; ?]\n";
    }
  }

  public function addCredentialCondition($content, $params = array())
  {
    if (isset($params['credentials']))
    {
      $content = sprintf("
[?php if (\$sf_user->hasCredential(%s)): ?]
  %s
[?php endif; ?]
", $this->renderCredentials($params['credentials']), $content);
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
  public function getLinkToAction($actionName, $params, $object_link = false)
  {
    $route  = isset($params['route']) ? $params['route'] : null;
    $action = isset($params['action']) ? $params['action'] : 'List'.sfInflector::camelize($actionName);
    $label  = isset($params['label']) ? $params['label'] : sfInflector::humanize($actionName);

    if (isset($params['confirm'])) {
      $params['confirm'] = $this->renderPhpArrayText($params['confirm']);
    }

    if (isset($params['title'])) {
      $params['title'] = $this->renderPhpArrayText($params['title']);
    }

    // Not a "link_to" attribute
    unset($params['action'], $params['label'], $params['route'], $params['object_link'], $params['credentials']);

    $sf_subject  = $object_link ? '$'.$this->getSingularName() : null;
    $urlOptions  = array();
    $linkOptions = $params;

    if ($route) {
      $route = $this->asPhp($route);
    }
    else {
      $route = $this->urlFor($object_link ? 'object' : 'collection', false);
      $urlOptions['action'] = $action;
    }

    if ($sf_subject) {
      $urlOptions['sf_subject'] = $this->parser->wrapPhpToken($sf_subject);
    }

    // Old style URL
    if (strpos($route, "'@") === 0) {
      $options = $urlOptions ? array_merge($urlOptions, $linkOptions) : $linkOptions;
      return $this->_renderOldStyleRoute($this->renderPhpText($label), $route, $this->parser->renderArray($options));
    }

    $urlOptions = count($urlOptions) == 1 && isset($urlOptions['sf_subject']) ? $sf_subject : $this->parser->renderArray($urlOptions);

    return $this->_renderNewStyleRoute($this->renderPhpText($label), $route, $urlOptions, $this->parser->renderArray($linkOptions));
  }

  protected function _renderOldStyleRoute($label, $route, $options)
  {
    return $this->parser->replaceTokens(sprintf('[?php echo link_to(%s, %s, %s) ?]', $label, $route, $options), null);
  }

  protected function _renderNewStyleRoute($label, $route, $urlOptions, $linkOptions)
  {
    return $this->parser->replaceTokens(sprintf('[?php echo link_to(%s, %s, %s, %s) ?]', $label, $route, $urlOptions, $linkOptions), null);
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

  /**
   * Generates classes and templates in cache.
   *
   * @param array $params The parameters
   *
   * @return string The data to put in configuration cache
   */
  public function generate($params = array())
  {
    $params = $this->validateParameters($params);
    $this->saveParams($params);

    // theme exists?
    if (!is_dir($themeDir = $this->getThemeDirectory())) {
      throw new sfConfigurationException(sprintf('The theme "%s" does not exist.', $this->getTheme()));
    }

    // configure the model
    $this->configure();

    $this->configuration = $this->loadConfiguration();
    $this->parser        = $this->getTokenParser();
    $files               = $this->getPhpFilesToGenerate($themeDir);

    // generate files
    $this->generatePhpFiles($this->generatedModuleName, $files);

    return sprintf("require_once('%s/%s/actions/actions.class.php');", sfConfig::get('sf_module_cache_dir'), $this->generatedModuleName);
  }

  protected function saveParams($params)
  {
    $this->params = $params;
    $this->configToOptions($params);
    $this->setModuleName($params['moduleName']);
    $this->modelClass = $params['model_class'];
    $this->setGeneratedModuleName('auto'.ucfirst($this->getModuleName()));
    $this->options['singular_name'] = $this->getSingularName();
    $this->options['class_label']   = $this->getClassLabel();
    $this->setTheme(isset($params['theme']) ? $params['theme'] : 'default');
    $this->themeDir = $this->generatorManager->getConfiguration()->getGeneratorTemplate($this->getGeneratorClass(), $this->getTheme(), '');
  }

  /**
   * Loads the configuration for this generated module.
   */
  protected function loadConfiguration()
  {
    $class = $this->getConfigurationClass();
    $configuration = new $class($this->config, $this->params);
    $this->configToOptions($configuration->getConfiguration());

    return $configuration;
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

    return $params;
  }

  protected function renderCredentials($credentials)
  {
    if (is_array($credentials) && count($credentials) == 1
      && isset($credentials[0]) && is_string($credentials[0])) {
      $credentials = $credentials[0];
    }

    return is_array($credentials) ? $this->parser->renderArray($credentials) : $this->asPhp($credentials);
  }

  // Provides a hook to change generated files
  protected function getPhpFilesToGenerate($themeDir)
  {
    return sfFinder::type('file')->relative()->in($themeDir);
  }

  protected function getTokenParser()
  {
    return new sfThemeTokenParser($this->options, $this->getSingularName(), $this->getI18nCatalogue());
  }

  protected function getConfigurationClass()
  {
    return 'sfThemeGeneratorConfiguration';
  }
}
