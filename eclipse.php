<?php
include_once "xml.php";
include_once "invoker.php";
include_once "socket.php";
class EclipseReporter extends XmlReporter {
	var $_port;
	function EclipseReporter($port){
		$this->_port = $port;
		$this->XmlReporter();
	}
	
	function &createInvoker(&$invoker) {
		$eclinvoker = &new SimpleErrorTrappingInvoker(new EclipseInvoker($invoker->getTestCase(),$this->_port));
		return $eclinvoker;
	}
	
	function paintMethodStart($method) {
		parent::paintGroupStart($method, $this->_size);
		parent::paintCaseStart($method);
		parent::paintMethodStart($method);
	}
	
	function paintMethodEnd($method){
		parent::paintMethodEnd($method);
		parent::paintCaseEnd($method);
		parent::paintGroupEnd($method);
		
	}
	
	function paintCaseStart($method){
		//do nothing
	}
	function paintCaseEnd($method){
		//do nothing
	}
	function paintGroupStart($method,$size){
		//do nothing
	}
	function paintGroupEnd($method){
		//do nothing
	}
}

class EclipseInvoker extends SimpleInvoker{
	var $_port;
	function EclipseInvoker(&$test_case,$port) {
		$this->_port = $port;
		$this->_test_case = &$test_case;
	}
	
	function invoke($method) {
		ob_start();
		$this->_test_case->before($method);
		$this->_test_case->setUp();
		$this->_test_case->$method();
		$this->_test_case->tearDown();
		$this->_test_case->after($method);
		$output = ob_get_contents();
		ob_end_clean();

		$sock = new SimpleSocket("127.0.0.1",$this->_port,5);
		$sock->write($output);
		$sock->close();
		echo $sock->getError();
	}
}
	
?>