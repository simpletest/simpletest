// jsreporter.js
// Script to support JsReporter class
// Relies heavily on the X library in x.js
//     X v3.14.1, Cross-Browser DHTML Library from Cross-Browser.com
// Copyright (c) 2004 Jason E. Sweat (jsweat_php@yahoo.com)
// 
// SimpleTest - http://simpletest.sf.net/
// Copyright (c) 2003,2004 Marcus Baker (marcus@lastcraft.com)
// $Id$

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

function activate_tab(tab) {
}

function make_fail(fails) {
}

function make_tree(groups, cases, methods) {
}

function make_output(data) { 
}

function make_fail_msg(id, msg) {
}

// Variables:
