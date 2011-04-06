<?php

require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test(null, new lime_output_color());

$tokens = array('real_token' => 'REAL_TOKEN');
$varname = 'testvar';
$parser = new sfThemeTokenParser($tokens, $varname);

$t->info('test "replaceTokens" function');
$t->is($parser->replaceTokens('test'), 'test', 'string without token is unaffected');
$t->is($parser->replaceTokens('test %%real_token%%'), 'test REAL_TOKEN', 'strings containing token replaced successfully');

// We should replace these ugly strings
$t->is($parser->replaceTokens('test %%to_string%%'), '<?php echo \'test \'.$testvar.\'\' ?>', 'special "to_string" token replaced successfully');
$t->is($parser->replaceTokens('test %%fake%%'), '<?php echo \'test \'.$testvar->getFake().\'\' ?>', 'getter token replaced successfully');


