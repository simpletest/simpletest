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
}

function set_div_content(div, content) {
	var ele;
	ele = xGetElementById(div);
	ele.innerHTML = content;
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

