function getBaseHref() {
	var href = document.getElementsByTagName('base')[0].href;
	if (href == null) {
		return '/';
	} else {
		return href;
	}
}

function popup(code, w, h){
    window.open(getBaseHref() + 'plug.php?o=' + code, '', 'toolbar=0,location=0,directories=0,menuBar=0,resizable=0,scrollbars=yes,width=' + w + ',height=' + h + ',left=32,top=16');
}

function pfs(id, c1, c2){
    window.open(getBaseHref() + 'pfs.php?userid=' + id + '&c1=' + c1 + '&c2=' + c2, 'PFS', 'status=1, toolbar=0,location=0,directories=0,menuBar=0,resizable=1,scrollbars=yes,width=754,height=512,left=32,top=16');
}

function redirect(url){
    location.href = url.options[url.selectedIndex].value;
}

function toggleblock(id){
    var bl = document.getElementById(id);
    if (bl.style.display == 'none') {
        bl.style.display = '';
    }
    else {
        bl.style.display = 'none';
    }
	return false;
}

// Inserts text into textarea at cursor position
function insertText(docObj, fieldName, value) {
	var field = null;
	if(!docObj)
		docObj = document;
	// Find the field in the docObj
	findField:
	for(var i = 0; i < docObj.forms.length; i++) {
		for(var j = 0; j < docObj.forms[i].elements.length; j++) {
			if(docObj.forms[i].elements[j].name == fieldName) {
				field = docObj.forms[i].elements[j];
				break findField;
			}
		}
	}
	if(!field)
		return false;
	// Insert the text
	if (docObj.selection) {
		// MSIE and Opera
		field.focus();
		var sel = docObj.selection.createRange();
		sel.text = value;
	} else if (field.selectionStart || field.selectionStart == 0) {
		// Mozilla
		var startPos = field.selectionStart;
		var endPos = field.selectionEnd;
		field.value = field.value.substring(0, startPos) + value + field.value.substring(endPos, field.value.length);
	} else {
		field.value += value;
	}
	return true;
}

// Array of ajax error handlers
// Example of use:
// ajaxErrorHandlers.push({divId: 'ajaxBlock', func: myErrorHandler});
var ajaxErrorHandlers = new Array();
var ajaxSuccessHandlers = new Array();
// AJAX enablement defaults to false
var ajaxEnabled = false;
// Required to calculate paths
var ajaxCurrentBase = location.href.replace($('base').eq(0).attr('href'), '').replace(/\?.*$/, '').replace(/#.*$/, '');
// This flag indicates that AJAX+history has been used on this page
// It means that "#" or home link should be loaded via ajax too
var ajaxUsed = false;
// Global flag to let everybody know that AJAX has failed
var ajaxError = false;

/**
 * AJAX helper function
 * @param {hash} settings A hashtable with settings
 * @return FALSE on successful AJAX call, TRUE on error to continue in
 * synchronous mode
 * @type bool
 */
function ajaxSend(settings) {
	var method = settings.method ? settings.method.toUpperCase() : 'GET';
	var data = settings.data || '';
	var url = settings.url || $('#' + settings.formId).attr('action');
	if (method == 'POST') {
		data += '&' + $('#' + settings.formId).serialize();
	} else if (settings.formId) {
		var sep = url.indexOf('?') > 0 ? '&' : '?';
		url += sep + $('#' + settings.formId).serialize();
	}
	$.ajax({
		type: method,
		url: encodeURI(url),
		data: data,
		beforeSend: function() {
			$('#' + settings.divId).append('<span style="position:absolute;left:' + ($('#' + settings.divId).width()/2 - 110) + 'px;top:' + ($('#' + settings.divId).height()/2 + 9) + 'px;" class="loading" id="loading"><img src="./images/spinner.gif" alt="loading"/></span>');
		},
		success: function(msg) {
			$('#loading').remove();
			$('#' + settings.divId).hide().html(msg).fadeIn(500);
			for (var i = 0; i < ajaxSuccessHandlers.length; i++) {
				ajaxSuccessHandlers[i]();
			}
		},
		error: function(msg) {
			$('#loading').remove();
			if (ajaxErrorHandlers.length > 0) {
				for (var i = 0; i < ajaxErrorHandlers.length; i++) {
					if (ajaxErrorHandlers[i].divId == settings.divId)
						ajaxErrorHandlers[i].func(msg);
				}
			} else {
				alert('AJAX error: ' + msg);
				ajaxError = true;
			}
		}
	});
	return false;
}

/**
 * AJAX subpage loader with history support
 * @param {string} hash A hash-address string
 * @return FALSE on successful AJAX call, TRUE on error to continue in
 * synchronous mode
 * @type bool
 */
function ajaxPageLoad(hash) {
	var m = hash.match(/^get(-.*?)?;(.*)$/);
	if (m) {
		// ajax bookmark
		var url = m[2].indexOf(';') > 0 ? m[2].replace(';', '?') : ajaxCurrentBase + '?' + m[2];
		ajaxUsed = true;
		return ajaxSend({
			method: 'GET',
			url: url,
			divId: m[1] ? m[1].substr(1) : 'ajaxBlock'
		});
	} else if (hash == '' && ajaxUsed) {
		// ajax home
		return ajaxSend ({
			url: location.href.replace(/#.*$/, ''),
			divId: 'ajaxBlock'
		});
	}
	return true;
}

/**
 * AJAX subform loader without history tracking
 * @param {string} hash A hash-address string
 * @param {string} formId Target form id attribute
 * @return FALSE on successful AJAX call, TRUE on error to continue in
 * synchronous mode
 * @type bool
 */
function ajaxFormLoad(hash, formId) {
	var m = hash.match(/^(get|post)(-.*?)?;(.*)$/);
	if (m) {
		// ajax bookmark
		var url = m[3].indexOf(';') > 0 ? m[3].replace(';', '?') : ajaxCurrentBase + '?' + m[3];
		ajaxUsed = true;
		return ajaxSend({
			method: m[1].toUpperCase(),
			url: url,
			divId: m[2] ? m[2].substr(1) : 'ajaxBlock',
			formId: formId
		});
	}
	return true;
}

/**
 * Constructs ajaxable hash string
 * @param {string} href Link href or form action attribute
 * @param {string} rel An attribute value possibly containing a hash address
 * @param {string} formData Is passed for forms only, is 'post' for POST forms
 * or serialized form data for GET forms
 * @return A valid hash-address string
 * @type string
 */
function ajaxMakeHash(href, rel, formData) {
	var hash = (formData == 'post') ? 'post' : 'get';
	var hrefBase, params;
	var sep = '?';
	var m = rel.match(/(get|post)(-[^ ;]+)?(;\S*)?$/);
	if (m) {
		hash = m[1];
		if (m[2]) {
			hash += m[2];
		}
		if (m[3]) {
			href = m[3].substr(1);
			sep  = ';';
		}
	}
	hash += ';'
	if (href.indexOf(sep) > 0) {
		hrefBase = href.substr(0, href.indexOf(sep));
		params = href.substr(href.indexOf(sep) + 1);
		if (formData && formData != 'post') {
			params += '&' + formData;
		}
	} else {
		hrefBase = href;
		params = '';
		if (formData && formData != 'post') {
			params += sep + formData;
		}
	}
	hash += hrefBase == ajaxCurrentBase ? params : hrefBase + ';' + params;
	return hash;
}

/**
 * Standard event bindings
 */
function bindHandlers() {
	if (location.hash == '#comments' || location.hash.match(/#c\d+/)) {
		$('.comments').css('display', '');
	}
	$('.comments_link').click(function() {
		if($('.comments').css('display') == 'none') {
			$('.comments').css('display', '');
		} else {
			$('.comments').css('display', 'none');
		}
	});

	if(location.href.match(/#comments/)) {
		$('.comments').css('display', '');
	}

	if (ajaxEnabled) {
		// AJAX auto-handling
		$('form.ajax').live('submit', function() {
			if ($(this).attr('method').toUpperCase() == 'POST') {
				ajaxFormLoad(ajaxMakeHash($(this).attr('action').replace(/#.*$/, ''), $(this).attr('class'), 'post'), $(this).attr('id'));
			} else {
				$.historyLoad(ajaxMakeHash($(this).attr('action').replace(/#.*$/, ''), $(this).attr('class'), $(this).serialize()));
			}
			return ajaxError;
		});
		$('a.ajax').live('click', function() {
			$.historyLoad(ajaxMakeHash($(this).attr('href').replace(/#.*$/, ''), $(this).attr('rel')));
			return ajaxError;
		});
	}
}

$(document).ready(function() {
	bindHandlers();
	if (ajaxEnabled) {
		$.historyInit(ajaxPageLoad, location.hash);
	}
});

window.name = 'main';
