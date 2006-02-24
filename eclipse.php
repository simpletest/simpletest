<?php
/**
*	base include file for eclipse plugin 
*	@package	SimpleTest
*	@version	$Id$
*/
include_once('unit_tester.php');
include_once('test_case.php');
include_once('invoker.php');
include_once('socket.php');
include_once('mock_objects.php');


class EclipseReporter extends SimpleScorer {
	var $_listener;
	function EclipseReporter(&$listener){
		$this->_listener = &$listener;
		$this->SimpleScorer();
		$this->_case = "";
		$this->_group = "";
		$this->_method = "";
	}
	
	function &createListener($port,$host="127.0.0.1"){
		$tmplistener = & new SimpleSocket($host,$port,5);
		return $tmplistener;
	}
	
	function &createInvoker(&$invoker){
		$eclinvoker = & new EclipseInvoker(&$invoker, &$this->_listener);
		return $eclinvoker;
	}
	
	function escapeVal($val){
		$needle = array("\\","\"","/","\b","\f","\n","\r","\t");
		$replace = array('\\\\','\"','\/','\b','\f','\n','\r','\t');
		return str_replace($needle,$replace,$val);
	}
	
	function paintPass($message){
		if (!$this->_pass){
			$this->_message = $this->escapeVal($message);
		}
		$this->_pass = true;
	}
	
	function paintFail($message){
		$this->_fail = true;
		$this->_message = $this->escapeVal($message);
		echo '{status:"fail",message:"'.$this->_message.'",group:"'.$this->_group.'",case:"'.$this->_case.'",method:"'.$this->_method.'"}';
	}
	
	function paintError($message){
		$this->_error = true;
		$this->_message = $this->escapeVal($message);
		echo '{status:"error",message:"'.$this->_message.'",group:"'.$this->_group.'",case:"'.$this->_case.'",method:"'.$this->_method.'"}';
	}
	
	function paintHeader($method){
	}
	
	function paintFooter($method){
	}
	
	function paintMethodStart($method) {
		$this->_pass = false;
		$this->_fail = false;
		$this->_error = false;
		$this->_method = $this->escapeVal($method);
	}
		
	function paintMethodEnd($method){	
		if ($this->_fail || $this->_error || !$this->_pass){
			//do nothing
		}else{
			//this ensures we only get one message per method that passes
			echo '{status:"pass",message:"'.$this->_message.'",group:"'.$this->_group.'",case:"'.$this->_case.'",method:"'.$this->_method.'"}';
		}
	}
	
	function paintCaseStart($case){
		$this->_case = $this->escapeVal($case);
	}
	
	function paintCaseEnd($case){
		$this->_case = "";
	}
	function paintGroupStart($group,$size){
		$this->_group = $this->escapeVal($group);
	}
	function paintGroupEnd($group){
		$this->_group = "";
	}
}

class EclipseInvoker extends SimpleInvokerDecorator{
	var $_listener;
	
	function EclipseInvoker(&$invoker,&$listener) {
		$this->_listener = &$listener;
		$this->SimpleInvokerDecorator(&$invoker);
	}
	
	function before($method){
		ob_start();
		$this->_invoker->before($method);
	}

	function after($method) {
		$this->_invoker->after($method);
		$output = ob_get_contents();
		ob_end_clean();
		$result = $this->_listener->write($output);
		if ($result == -1){
			$this->_listener->output = $output;
		}
		//debug: 
		//echo $output;
	}
	
	
}

?>