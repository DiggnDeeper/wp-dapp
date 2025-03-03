<?php
/**
 * WP-Dapp Regex Test Script
 * 
 * This script tests the regex patterns used to strip Gutenberg block comments
 * from WordPress content when publishing to Hive.
 * 
 * Run this script with: php regex-test.php
 */

// Sample WordPress Gutenberg content with various comment formats
$test_content = <<<EOT
<!-- wp:paragraph -->
<p>I'm very excited that I am finally making progress on this WordPress as a Hive front end idea.</p>
<!-- /wp:paragraph -->

<!--wp:paragraph-->
<p>Expect a few more of these first experimental "Hello World" posts as I work things out and shortly I'll start putting out more useful information.</p>
<!--/wp:paragraph-->

<!-- wp:paragraph-->
<p>Ladies and Gentlemen, I'm stoked.</p>
<!--/wp:paragraph -->

<!--wp:paragraph -->
<p>Stay tuned!</p>
<!-- /wp:paragraph-->

<!-- wp:image {"id":123,"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="example.jpg" alt="Example" /></figure>
<!-- /wp:image -->

<!-- wp:heading -->
<h2>Multi-line
comment
example</h2>
<!-- /wp:heading -->
EOT;

echo "Original content:\n";
echo "----------------\n";
echo $test_content . "\n\n";

// Original regex pattern from the plugin
$old_pattern_result = $test_content;
$old_pattern_result = preg_replace('/<!--\s+wp:(.*?)\s+-->/', '', $old_pattern_result);
$old_pattern_result = preg_replace('/<!--\s+\/wp:(.*?)\s+-->/', '', $old_pattern_result);

echo "Result with old regex pattern:\n";
echo "----------------------------\n";
echo $old_pattern_result . "\n\n";

// Improved regex pattern
$new_pattern_result = $test_content;
$new_pattern_result = preg_replace('/<!--\s*wp:.*?(?:-->|\/-->)/s', '', $new_pattern_result); // Opening tags
$new_pattern_result = preg_replace('/<!--\s*\/wp:.*?(?:-->|\/-->)/s', '', $new_pattern_result); // Closing tags

echo "Result with improved regex pattern:\n";
echo "--------------------------------\n";
echo $new_pattern_result . "\n\n";

// Verify if all comments were removed
$has_comments = preg_match('/<!--.*?-->/s', $new_pattern_result);
echo "Verification test " . ($has_comments ? "FAILED" : "PASSED") . ": " . 
     ($has_comments ? "Comments still present!" : "All Gutenberg comments successfully removed!") . "\n"; 