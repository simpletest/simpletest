<?php
declare(strict_types=1);

// Reproduction for  Issue #48 — mocking interfaces with class type-hinted arguments
namespace Acme\Plugin {
    class Config
    {
    }

    interface PluginFactory
    {
        public function setConfig(Config $config);
    }
}
namespace {
    require_once __DIR__ . '/../../src/mock_objects.php';

    print "[Issue 48] Global interface test:\n";

    // Global (non-namespaced) case
    class Config
    {
    }

    interface PluginFactory
    {
        public function setConfig(Config $config);
    }

    try {
        Mock::generate('PluginFactory');
        print "Mock::generate('PluginFactory') completed.\n";

        $generated = 'Mock' . (new ReflectionClass('PluginFactory'))->getShortName();

        if (\class_exists($generated)) {
            print "{$generated} exists, attempting instantiation...\n";
            $m = new $generated;
            print "Instantiated {$generated} successfully.\n";
        } else {
            print "{$generated} class not defined.\n";
        }
    } catch (Throwable $e) {
        print 'Global test threw: ' . $e->getMessage() . "\n";
    }

    print "\n[Issue 48] Namespaced interface test:\n";

    try {
        $ret = Mock::generate('Acme\\Plugin\\PluginFactory');
        print "Mock::generate('Acme\\Plugin\\PluginFactory') returned:\n";

        // var_dump($ret);

        $mockName = 'Acme\\Plugin\\Mock' . (new ReflectionClass('Acme\\Plugin\\PluginFactory'))->getShortName();

        if (\class_exists($mockName)) {
            print "Namespaced mock class {$mockName} exists, attempting instantiation...\n";
            $m = new $mockName;
            print "Instantiated {$mockName} successfully.\n";
        } else {
            print "Namespaced mock class {$mockName} not defined.\n";
        }
    } catch (Throwable $e) {
        print 'Namespaced test threw: ' . $e->getMessage() . "\n";
    }
}
