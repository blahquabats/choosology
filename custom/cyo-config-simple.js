/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	
	// %REMOVE_START%
	// The configuration options below are needed when running CKEditor from source files.
	config.plugins = 'dialogui,dialog,about,a11yhelp,basicstyles,clipboard,panel,floatpanel,menu,contextmenu,resize,button,toolbar,elementspath,enterkey,entities,popup,filebrowser,floatingspace,listblock,richcombo,format,horizontalrule,htmlwriter,wysiwygarea,image,indent,indentlist,fakeobjects,link,list,magicline,maximize,pastetext,removeformat,showborders,specialchar,menubutton,scayt,tab,undo,wsc,panelbutton,lineutils,widget,image2,font,imagebrowser,symbol,lineheight,fixed,donothing';
	config.skin = 'icy_orange';
	// %REMOVE_END%

	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		//{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		
		//{ name: 'colors' },
		//{ name: 'links' },
		//{ name: 'insert' },
		//{ name: 'forms' },
		//{ name: 'tools' },
		//{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		//{ name: 'others' },
		//'/',
		
		//{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align' ] },
		{ name: 'styles' },
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		//{ name: 'about' }
	];

	// Remove some buttons provided by the standard plugins, which are
	// not needed in the Standard(s) toolbar.
	config.removeButtons = 'Superscript,Subscript,Strikethrough';
	
	config.removePlugins = 'youtube,bidi,blockquote,lineheight';

	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Simplify the dialog windows.
	config.removeDialogTabs = 'image:advanced;link:advanced';
	config.autoParagraph= false;
	config.ignoreEmptyParagraph =  true;
};
