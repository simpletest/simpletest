// jsreporter.js
// Script to support JsReporter class
// Relies heavily on the X library in x.js
//     X v3.14.1, Cross-Browser DHTML Library from Cross-Browser.com
// Copyright (c) 2004 Jason E. Sweat (jsweat_php@yahoo.com)
// 
// SimpleTest - http://simpletest.sf.net/
// Copyright (c) 2003,2004 Marcus Baker (marcus@lastcraft.com)
// $Id$


// Variables:
wu_min_x=500;
wu_min_y=400;
wu_fail_content="";
wu_tree_content="";

// Functions:
function wait_start() {
  var wait_x;
  var wait_y;

  wait_x = xWidth('wait');
  wait_y = xHeight('wait');
  xMoveTo('wait', (xClientWidth()-wait_x)/2, (xClientHeight()-wait_y)/2);
  xShow('wait');
}

function layout() {
/*	var new_x;
	var new_y;
	
	new_x = max(xClientWidth()-6,wu_min_x);
	new_y */
	xResizeTo('webunit', max(xClientWidth()-20,wu_min_x), max(xClientHeight()-20,wu_min_y));
	xMoveTo('webunit', 5, 5);
	xShow('webunit');
}

function set_div_content(div, content) {
	xGetElementById(div).innerHTML = content;
}

function copy_div_content(divsrc, divtrgt) {
	xGetElementById(divtrgt).innerHTML = xGetElementById(divsrc).innerHTML;
}

function activate_tab(tab) {
	alert('change to '+tab+'tab');
}

function make_fail(fails) {
}

function make_tree(groups, cases, methods) {
}

function make_output(data) { 
}

function make_fail_msg(id, msg) {
}

function max(n1, n2) {
  if (n1 > n2) {
  	return n1;
  } else {
  	return n2;
  }
}
