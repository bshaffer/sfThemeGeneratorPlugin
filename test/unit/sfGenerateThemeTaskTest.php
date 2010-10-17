<?php

include dirname(__FILE__).'/../bootstrap/bootstrap.php';

$cleanup = new sfGenerateThemeTaskCleanup();
$cleanup->cleanup();

$project_dir    = sfConfig::get('sf_project_dir');
$app_dir        = sfConfig::get('sf_app_dir');

$t = new sfGenerateThemeLimeTest();
$t->configuration = $configuration;

$t->diag('sfGenerateThemeTask');
$t->task_ok(array('theme' => 'test'), array('application' => 'frontend', 'model' => 'Company', 'module' => 'company'));

$t->ok(is_dir($app_dir.'/modules/company'), 'The "company" module has been generated');
$t->ok(file_exists($app_dir.'/templates/custom.php'), 'The "custom.php" file (outside the module directory) has been copied correctly');
$t->like(file_get_contents($app_dir.'/config/routing.yml'), '/company_test_route/', 'The route "company" has been added to the routing.yml file');

$cleanup->cleanup();
