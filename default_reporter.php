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
    /**#@-*/

    /**
     *    The default reporter used by SimpleTest's autorun
     *    feature.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class DefaultReporter extends SimpleReporterDecorator {
        
        /**
         *  Assembles the apropriate reporter for the environment.
         */
        function DefaultReporter() {
            if (SimpleReporter::inCli()) {
                $reporter = &new SelectiveReporter(new TextReporter(), @$argv[1], @$argv[2]);
            } else {
                $reporter = &new SelectiveReporter(new HtmlReporter(), @$_GET['c'], @$_GET['t']);
            }
            $this->SimpleReporterDecorator($reporter);
        }
    }
?>