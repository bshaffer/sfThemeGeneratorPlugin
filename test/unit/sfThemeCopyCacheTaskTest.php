<?php

include dirname(__FILE__).'/../bootstrap/bootstrap.php';

$cleanup = new sfThemeGenerateTaskCleanup();
$cleanup->cleanup();

$project_dir    = sfConfig::get('sf_project_dir');
$app_dir        = sfConfig::get('sf_app_dir');
$module_dir     = $app_dir.'/modules/company';

$t = new sfThemeGenerateLimeTest();
$t->configuration = $configuration;

$t->info('generate the module first');
$t->task_ok('sfThemeGenerateTask', array('theme' => 'admin'), array('application' => 'frontend', 'model' => 'Company', 'module' => 'company'));

$t->diag('sfThemeCopyCacheTask');
$t->task_ok('sfThemeCopyCacheTask', array('application' => 'frontend', 'module' => 'company'), array('force' => true));

$t->ok(is_dir($module_dir), 'The "company" module has been generated');
$t->ok(file_exists($module_dir.'/templates/indexSuccess.php'), 'The "indexSuccess.php" file has been copied correctly');
$t->ok(file_exists($module_dir.'/templates/_form.php'), 'The "_form.php" file has been copied correctly');

$cleanup->cleanup();
