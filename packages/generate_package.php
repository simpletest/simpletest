<?php

/*
//  the following packages are presumed to be present in the base install:

PACKAGE        VERSION STATE
Archive_Tar    1.1     stable
Console_Getopt 1.2     stable
DB             1.6.1   stable
Mail           1.1.2   stable
Net_SMTP       1.2.5   stable
Net_Socket     1.0.1   stable
PEAR           1.3.1   stable
PHPUnit        0.6.2   stable
XML_Parser     1.0.1   stable
XML_RPC        1.1.0   stable
*/

set_time_limit(0);
require_once 'PEAR/PackageFileManager.php';

$packagexml = new PEAR_PackageFileManager;
$e = $packagexml->setOptions(array(
    'baseinstalldir' => 'simpletest',
    'version' => '1.0.0',
    'license' => 'The Open Group Test Suite License',
    'packagedirectory' => '/var/www/html/tmp/simpletest',
    'state' => 'stable',
    'package' => 'simpletest',
    'simpleoutput' => true,
    'summary' => 'PHP Simple Test',
    'description' => 'A framework for unit testing, web site testing and mock objects for PHP 4.2.0+',
    'filelistgenerator' => 'file', // generate from cvs, use file for directory
    'notes' => 'See the CHANGELOG for full list of changes',
    'dir_roles' => array(
        'docs' => 'doc',
        'extensions' => 'php',
        'packages' => 'data',
        'test' => 'test',
        'tutorials' => 'doc',
        'ui' => 'php',
        ),
    'ignore' => array(
        'generatePackage.php', 
//        'packages/', 
//        'tutorials/',
//        'ui/',
        '*CVS*',
        ), 
    'roles' => array(
        'php' => 'php',
        'html' => 'php',
        '*' => 'php',
         ),
//    'exceptions' => array(
//        'TODO' => 'doc',
//        'VERSION' => 'doc',
//        'HELP_MY_TESTS_DONT_WORK_ANYMORE' => 'doc',
//        'LICENSE' => 'doc',
//        'README' => 'data',
//        ),
    )
);
if (is_a($e, 'PEAR_Error')) {
    echo $e->getMessage();
    die();
}

$e = $packagexml->addMaintainer('lastcraft', 'lead', 'Marcus Baker', 'marcus@lastcraft.com');
if (is_a($e, 'PEAR_Error')) {
    echo $e->getMessage();
    exit;
}

// note use of {@link debugPackageFile()} - this is VERY important
if (isset($_GET['make']) || (isset($_SERVER['argv'][2]) &&
      $_SERVER['argv'][2] == 'make')) {
    $e = $packagexml->writePackageFile();
} else {
    $e = $packagexml->debugPackageFile();
}
if (is_a($e, 'PEAR_Error')) {
    echo $e->getMessage();
    die();
}
?>