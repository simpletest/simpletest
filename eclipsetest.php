<?php
	/**
	 *	base include file for Eclipse plugin test code
	 *	@package	SimpleTest
	 *	@subpackage	UnitTester
	 *	@version	$Id$
	 */
	
    /**#@+
     * include SimpleTest files
     */
	require_once(dirname(__FILE__) . '/socket.php');
	require_once(dirname(__FILE__) . '/test_case.php');
	/**#@-*/
	
	/**
     *    Standard unit test class for eclpse plugin testing of SimpleTest code.
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
	class EclipseTest extends GroupTest {
		
		/**
         *    Sets the name of the test suite.
         *    @param string $label    Name sent at the start and end
         *                            of the test.
         *    @access public
         */
		function EclipseTest($label){
			parent::GroupTest($label);
		}
		
		/**
	    *    Invokes run() on all of the held test cases, instantiating
	    *    them if necessary.
	    *    @param SimpleReporter $reporter    Current test reporter.
	    *    @param integer $port               Port to report test results on.
	    *    @access public
	    */
	   function run(&$reporter, $port) {
		   $count = count($this->_test_cases);
	       for ($i = 0; $i < $count; $i++) {
				if (is_string($this->_test_cases[$i])) {
					$class = $this->_test_cases[$i];
		           	$test = &new $class();
		
		           	ob_start();
					$reporter->paintGroupStart($this->getLabel(), $this->getSize());
	                $reporter->paintCaseStart($test->getLabel());
	                $start = ob_get_contents();
					ob_end_clean();
					
					ob_start();
					$reporter->paintCaseEnd($test->getLabel());
					$reporter->paintGroupEnd($this->getLabel());
			        $end = ob_get_contents();
					ob_end_clean();
					
					//the guts from SimpleTestCase::run($reporter) where 
	                $test->_runner = new EclipseRunner($test,$reporter,$start,$end,$port);
	                $test->_runner->run();
				
					$output = $start;
					if (($i+1) == $count){
					$output.= "<done/>";
					}
					$output.= $end;
	    			
					$sock = new SimpleSocket("127.0.0.1",$port,5);
					$sock->write($output);
					$sock->close();
					echo $sock->getError();
				
		       	} else {
					$this->_test_cases[$i]->run($reporter,$port);
		        }
				
	       }
	      
	       return $reporter->getStatus();
	   }
	   
	    /**
	    *    Builds a group test from a class list.
	    *    @param string $title       Title of new group.
	    *    @param array $classes      Test classes.
	    *    @return GroupTest          Group loaded with the new
	    *                               test cases.
	    *    @access private
	    */
		function _createGroupFromClasses($title, $classes) {
			static $group;
	   		if (!isset($group)){
	   			$group = new EclipseTest($title);
	   		}
	   		foreach ($classes as $class) {
	       		if (SimpleTest::isIgnored($class)) {
	           		continue;
	       		}
		   
		   		$tmpclass = &new $class();
		   		if (is_subclass_of($tmpclass,"GroupTest")){
				   	$tmptestclasses = $tmpclass->_test_cases;
				   	foreach ($tmptestclasses as $tmptestclass){
				   		$this->_createGroupFromClasses($title,$tmptestclass->_test_cases);
			   		}
		  	 	}else{
					$group->addTestClass($class);
				}
	   		}
	   		return $group;
	   }
		
	}
 	/**
     *    Runner converted for use with Eclipse plugin
     *    Major difference is that this runner returns an xml dataset
     *    after each test.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
	class EclipseRunner {
		
		/**
         *    Takes in the test case and reporter to mediate between.
         *    @param SimpleTestCase $test_case  Test case to run.
         *    @param SimpleScorer $scorer       Reporter to receive events.
         *    @param String $start				Text to prepend
         *    @param String $end				Text to postpend
         *    @param String $port				Port to report results to
         */
		function EclipseRunner(&$test_case, &$scorer,$start,$end,$port) {
	        $this->_test_case = &$test_case;
	        $this->_scorer = &$scorer;
			$this->_start = $start;
			$this->_end = $end;
			$this->_port = $port;
	    }
		
		/**
         *    Runs the test methods in the test case, or not if the
         *    scorer blocks it.
         *    @param SimpleTest $test_case    Test case to run test on.
         *    @param string $method           Name of test method.
         *    @access public
         */
		function run() {
	        $methods = get_class_methods(get_class($this->_test_case));
	        $invoker = &$this->_test_case->createInvoker();
	        foreach ($methods as $method) {
	            if (! $this->_isTest($method)) {
	                continue;
	            }
	            if ($this->_isConstructor($method)) {
	                continue;
	            }
	
				ob_start();
				echo $this->_start;
	            $this->_scorer->paintMethodStart($method);
	            if ($this->_scorer->shouldInvoke($this->_test_case->getLabel(), $method)) {
	                $invoker->invoke($method);
	            }
	            $this->_scorer->paintMethodEnd($method);
				echo $this->_end;
				$output = ob_get_contents();
				ob_end_clean();
	
				$sock = new SimpleSocket("127.0.0.1",$this->_port,5);
				$sock->write($output);
				$sock->close();
				echo $sock->getError();
				
	        }
	    }
	}
?>