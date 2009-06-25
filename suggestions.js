autocomplete = new function(){
	this.autocomp_ds = new YAHOO.widget.DS_XHR('getsuggestions.php', ['results', 'match']);
	this.autocomp_ds.responseType = YAHOO.widget.DS_XHR.TYPE_JSON;
	
	this.autocomp = new YAHOO.widget.AutoComplete(
		'searchbox', 'searchcontainer', this.autocomp_ds);
	
	document.getElementById('searchbox').focus();
};