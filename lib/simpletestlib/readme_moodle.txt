Description of Simpletest 1.0.1 library import into Moodle

Obtained from http://www.simpletest.org/en/download.html

Changes:
 * test_case.php - added our global $CFG before include() MDL-10064
 * fixed exception support (MDL-17534) - try/catch in invoker.php and errors.php
 * Bug fix in simpletest.php and test_case.php. Marked with //moodlefix begins,
   //moodlefix ends comments. This has been reported back to the simpletest mailing
   list. Hopefully will be included in a future release.
 * modified run() in test_case.php - skipping tests that need fake db if prefix not set

skodak, Tim

$Id: readme_moodle.txt,v 1.6 2009/01/10 16:06:53 skodak Exp $
