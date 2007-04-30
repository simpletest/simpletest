<?php
    /**
     *	Optional include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/scorer.php');
    require_once(dirname(__FILE__) . '/reporter.php');
    require_once(dirname(__FILE__) . '/xml.php');
    /**#@-*/
    
	/**
	 *    Parser for command line arguments. Extracts
	 *    the a specific test to run and engages XML
	 *    reporting when necessary.
	 *    @package SimpleTest
	 *    @subpackage UnitTester
	 */
	class SimpleCommandLineParser {
		var $_to_long_form = array(
				'case' => '_case', 'c' => '_case',
				'test' => '_test', 't' => '_test',
				'xml' => '_xml', 'x' => '_xml');
		var $_case = '';
		var $_test = '';
		var $_xml = false;
		
		function SimpleCommandLineParser($arguments) {
			if (! is_array($arguments)) {
				return;
			}
			foreach ($arguments as $i => $argument) {
				if (preg_match('/^--?(test|case|t|c)=(.+)$/', $argument, $matches)) {
					$command = $this->_to_long_form[$matches[1]];
					$this->$command = $matches[2];
				} elseif (preg_match('/^--?(test|case|t|c)$/', $argument, $matches)) {
					$command = $this->_to_long_form[$matches[1]];
					if (isset($arguments[$i + 1])) {
						$this->$command = $arguments[$i + 1];
					}
				} elseif (preg_match('/^--?(xml|x)$/', $argument)) {
					$this->_xml = true;
				}
			}
		}
		
		function getTest() {
			return $this->_test;
		}
		
		function getTestCase() {
			return $this->_case;
		}
		
		function isXml() {
			return $this->_xml;
		}
	}

    /**
     *    The default reporter used by SimpleTest's autorun
     *    feature.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class DefaultReporter extends SimpleReporterDecorator {
        
        /**
         *  Assembles the appopriate reporter for the environment.
         */
        function DefaultReporter() {
            if (SimpleReporter::inCli()) {
				global $argv;
				$parser = new SimpleCommandLineParser($argv);
				$reporter_class = $parser->isXml() ? 'XmlReporter' : 'TextReporter';
                $reporter = &new SelectiveReporter(
						new $reporter_class(), $parser->getTestCase(), $parser->getTest());
            } else {
                $reporter = &new SelectiveReporter(new HtmlReporter(), @$_GET['c'], @$_GET['t']);
            }
            $this->SimpleReporterDecorator($reporter);
        }
    }
?>