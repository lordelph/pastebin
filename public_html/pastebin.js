/* Copyright 2000 SiteExperts.com/ InsideDHTML.com, LLC
   This code can be reusedas long as the above copyright notice
   is not removed */

function checkTab(el) {
  // Run only in IE
  // and if tab key is pressed
  // and if the control key is pressed
  if ((document.all) && (9==event.keyCode)) {
    // Cache the selection
    el.selection=document.selection.createRange(); 
    setTimeout("processTab('" + el.id + "')",0)
  }
}

function processTab(id) {
  // Insert tab character in place of cached selection
  document.all[id].selection.text=String.fromCharCode(9)
  // Set the focus
  document.all[id].focus()
}


function setSelectionRange(input, selectionStart, selectionEnd) {
 if (input.setSelectionRange) {
   input.focus();
   input.setSelectionRange(selectionStart, selectionEnd);
 }
 else if (input.createTextRange) {
   var range = input.createTextRange();
   range.collapse(true);
   range.moveEnd('character', selectionEnd);
   range.moveStart('character', selectionStart);
   range.select();
 }
}


/* Code contributed by Paul Brennan */
   
// replace the text area contents with original plus our new TAB
function replaceSelection (input, replaceString) {
   if (input.setSelectionRange) {
       var selectionStart = input.selectionStart;
       var selectionEnd = input.selectionEnd;
       input.value = input.value.substring(0, selectionStart)+
replaceString + input.value.substring(selectionEnd);

       if (selectionStart != selectionEnd){
           setSelectionRange(input, selectionStart, selectionStart +
   replaceString.length);
       }else{
           setSelectionRange(input, selectionStart +
replaceString.length, selectionStart + replaceString.length);
       }

   }else if (document.selection) {
       var range = document.selection.createRange();

       if (range.parentElement() == input) {
           var isCollapsed = range.text == '';
           range.text = replaceString;

            if (!isCollapsed)  {
               range.moveStart('character', -replaceString.length);
               range.select();
           }
       }
   }
}



function onTextareaKey(item,e)
{
	//catch tab key...
	if(navigator.userAgent.match("Gecko")){
	c=e.which;
	}else{
	c=e.keyCode;
	}
	if(c==9){
	replaceSelection(item,String.fromCharCode(9));
	setTimeout("document.getElementById('"+item.id+"').focus();",0);
	return false;
	}

	//auto-resize textarea?
	var minRows=10;
	var maxRows=40;
	if (true) {
		var textLines = item.value.split("\n").length;
		var elementLines = item.rows;
		
		var fittedLines=Math.min(Math.max(textLines, minRows),maxRows);
		if (fittedLines!=elementLines)
		{
			item.rows=fittedLines;
		}
	}
}

///////////////////////////////////////////////////////////
// functions used by the diff feature

function fliprows(from,to)
{
	var cells=document.getElementsByTagName('tr');
	var i;
	for (i=0; i<cells.length; i++)
	{
		var cell=cells.item(i);
		if (cell.className==from)
			cell.className=to;
	}
}

function showold()
{
	fliprows('new','hidenew');
	fliprows('hideold','old');
	document.getElementById('oldlink').style.background="#880000";
	document.getElementById('newlink').style.background="";
	document.getElementById('bothlink').style.background="";
}

function shownew()
{
	fliprows('hidenew','new');
	fliprows('old','hideold');
	document.getElementById('oldlink').style.background="";
	document.getElementById('newlink').style.background="#880000";
	document.getElementById('bothlink').style.background="";
}

function showboth()
{
	fliprows('hidenew','new');
	fliprows('hideold','old');
	document.getElementById('oldlink').style.background="";
	document.getElementById('newlink').style.background="";
	document.getElementById('bothlink').style.background="#880000";
}


///////////////////////////////////////////////////////////
// clipboard copy
// adapted from a post found here 
// http://www.gamedev.net/community/forums/topic.asp?topic_id=281951
//
// Other interesting articles on clipboard access
// http://kb.mozillazine.org/Granting_JavaScript_access_to_the_clipboard
// http://sawai.blogspot.com/2005/12/copying-data-to-clipboard-using.html

function copyToClipboard(txt)
{

 	if (window.clipboardData) 
	{
	
		// IE makes it easy
		window.clipboardData.setData("Text", txt);
		
	}
	else if (netscape.security.PrivilegeManager) 
	{ 
		//looks like we're running a flavour of Gecko, lets try something
		//more tricky...
		
		try
		{
			// not entirely sure what this does...
			netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
			
			// get a clipboard interface
			var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
			
			// get a  transferable interface
			var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
			
			
			// what kind of data are we copying? 
			trans.addDataFlavor('text/unicode');
			
			//get a supports-string interface
			var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
			
			var copytext=txt;
			
			str.data=copytext;
			
			trans.setTransferData("text/unicode",str,copytext.length*2);
			
			var clipid=Components.interfaces.nsIClipboard;
			
			if (!clipid) return false;
			
			//here we go..
			clip.setData(trans,null,clipid.kGlobalClipboard);
		
			//alert("Following info was copied to your clipboard:\n\n" + txt);
		}
		
		catch (e)
		{
			var showhelp=confirm("It was not possible to access your clipboard.\n\n"+
				e+"\n"+
				"Would you like to view the help page which may help "+
				"you enable this feature?"); 
			if (showhelp)
			{
				document.location="/?help=1";
			}
		}
	
	
	}
	
	return false;
}	

function gotoURL(url)
{
	document.location=url;
}

function showSpamForm()
{
	document.getElementById('spamform').style.display='';
	document.getElementById('processabuse').value=0;
}

function clipboard()
{
	var post=document.getElementById('code');
	copyToClipboard(post.innerText);
	
	var span=document.getElementById('copytoclipboard');
	span.innerHTML='<a href="javascript:clipboard()" title="Copy to clipboard">text copied to clipboard</a> | ';
	
}

function initPastebin()
{
	if (document.getElementById)
	{
		//add copy to clipboard feature? IE only at the moment..
		var span=document.getElementById('copytoclipboard');
		if (window.clipboardData && span)
		{
			span.innerHTML='<a href="javascript:clipboard()" title="Copy to clipboard">copy to clipboard</a> | ';
			
		}
		
		var radio;
		
		radio=document.getElementById('expiry_day');
		if (radio)
		{
			radio.onclick=function ()
			{
				var expiryinfo=document.getElementById('expiryinfo');
				expiryinfo.innerHTML="Good for IRC or IM conversations";
				
				document.getElementById('expiry_day_label').className='current';
				document.getElementById('expiry_month_label').className='';
				document.getElementById('expiry_forever_label').className='';
			}
			if (radio.checked)
				radio.onclick();
		}
		
		radio=document.getElementById('expiry_month');
		if (radio)
		{
			radio.onclick=function ()
			{
				var expiryinfo=document.getElementById('expiryinfo');
				expiryinfo.innerHTML="Good for email conversations / temporary data";
			
				document.getElementById('expiry_day_label').className='';
				document.getElementById('expiry_month_label').className='current';
				document.getElementById('expiry_forever_label').className='';
			}
			if (radio.checked)
				radio.onclick();
		}
		
		radio=document.getElementById('expiry_forever');
		if (radio)
		{
			radio.onclick=function ()
			{
				var expiryinfo=document.getElementById('expiryinfo');
				expiryinfo.innerHTML="Good for long term archival of useful snippets";
			
				document.getElementById('expiry_day_label').className='';
				document.getElementById('expiry_month_label').className='';
				document.getElementById('expiry_forever_label').className='current';
			}
			if (radio.checked)
				radio.onclick();
		}
		
	}
	
}