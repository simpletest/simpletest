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
min_x=500;
min_y=400;
groups = new Array();
cases = new Array();
current_group=0;
current_case=0;

Hash = {
	Set : function(foo,bar) {this[foo] = bar;},
	Get : function(foo) {return this[foo];}
}

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
	xResizeTo('webunit', max(xClientWidth()-30,min_x), max(xClientHeight()-20,min_y));
	xMoveTo('webunit', 5, 5);
	xResizeTo('tabs', xWidth('webunit')-10, xHeight('webunit')/3);
	xLeft('tabs', 5);
	xShow('webunit');
	xShow('tabs');
	activate_tab('tree');
	xShow('visible_tab');
	xZIndex('visible_tab', 2)
	xResizeTo('msg', xWidth('webunit')-17, xHeight('webunit')/3-20);
	xLeft('msg', 2);
	xTop('msg',2*xHeight('webunit')/3);
	xShow('msg');
}

function set_div_content(div, content) {
	xGetElementById(div).innerHTML = content;
}

function copy_div_content(divsrc, divtrgt) {
	xGetElementById(divtrgt).innerHTML = xGetElementById(divsrc).innerHTML;
}

function activate_tab(tab) {
	if (tab == 'fail') {
		copy_div_content('fail', 'visible_tab');
		xGetElementById('failtab').className = 'activetab';
		xZIndex('failtab', 3)
		xGetElementById('treetab').className = 'inactivetab';
		xZIndex('treetab', 1)
	}
	if (tab == 'tree') {
		copy_div_content('tree', 'visible_tab');
		xGetElementById('failtab').className = 'inactivetab';
		xZIndex('failtab', 1)
		xGetElementById('treetab').className = 'activetab';
		xZIndex('treetab', 3)
	}
}

function add_group(group_name) {
  groups[groups.length] = group_name;
  current_group = groups.length - 1;
  cases[current_group] = new Array();
}

function add_case(case_name) {
  var curgroup;
  curgroup = cases[current_group];
  cases[current_group][curgroup.length] = case_name;
  current_case = curgroup.length - 1;
}

function make_fail(fails) {
}

function make_tree() {
	var content;
	content = '<ul>';
	for (x in groups) {
		content += '<li>'+groups[x]+'<ul>';
		for (y in cases[x]) {
			content += '<li>'+cases[x][y]+'</li>';
		}
		content += '</ul></li>';
	}
	content += '</ul>';
	xGetElementById('tree').innerHTML = content;
	if (xGetElementById('treetab').className == 'activetab') { activate_tab('tree'); }
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
