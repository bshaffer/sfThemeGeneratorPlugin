<?php

include dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test();

$generator = new sfThemeGenerator(new sfGeneratorManager($configuration));

$config = array(
          'test1' => 'value1', 
          'test2' => array('value2'),
          'test'  => array('3' => 'value3', '4_test' => 'value4'),
          'test_test5'   => 'value5',
          'test_test6'   => array('test_test6' => 'value6'));
          
$generator->configToOptions($config);

$t->is($generator->get('test1'), 'value1', 'get non-nested value');
$t->is($generator->get('test2'), array('value2'), 'get non-nested value as array');
$t->is($generator->get('test_3'), 'value3', 'get nested value');
$t->is($generator->get('test_4_test'), 'value4', 'get nested value with multiple underscores');
$t->is($generator->get('test_test5'), 'value5', 'get non-nested value with nested syntax');
$t->is($generator->get('test_test6_test_test6'), 'value6', 'works with complicated syntax');
$t->is($generator->get('test7', 'value7'), 'value7', 'returns default value if nonexistant');