autocomplete = new function(){
   // setup the data source to use getsuggestions.php and expect json output
	this.autocomp_ds = new YAHOO.widget.DS_XHR('getsuggestions.php');
	this.autocomp_ds.responseType = YAHOO.widget.DS_XHR.TYPE_JSON;
   this.autocomp_ds.responseSchema = {
      resultsList : "results",
      fields : ["match", "sura", "ayah"]
   };

   // not sure if this should be 'cancelStaleRequests' or 'ignoreStaleResponses'
   this.autocomp_ds.connXhrMode = 'cancelStaleRequests';
	
	this.autocomp = new YAHOO.widget.AutoComplete(
		'searchbox', 'searchcontainer', this.autocomp_ds);

   // format the result before displaying it
   this.autocomp.resultTypeList = false;
   this.autocomp.formatResult = function(oResultData, sQuery, sResultMatch){
      var sura = oResultData.sura;
      var ayah = oResultData.ayah;
      var text = oResultData.match;

      // make the search word bold
      var q = sQuery.toLowerCase();
      var loc = text.toLowerCase().indexOf(' ' + q) + 1;
      if (loc == 0) loc = text.toLowerCase().indexOf(q);
      var newStr = text.substr(0, loc) + "<b>" + text.substr(loc, q.length) +
            "</b>" + text.substr(loc + q.length);

      // handle long ayahs
      if ((text.length > 50) && (loc + q.length > 40)){
         var delta = 30 - q.length;
         if (delta < 0) delta = 0;
         var str = newStr.substr(loc - delta);
         if (delta > 0) str = str.substr(str.indexOf(' '));
         newStr = str;
      }
      return newStr;
   };

   // handle redirection to quran.com upon item selection
   itemSelectHandler = function(sType, sArgs){
      var sura = sArgs[2]['sura'];
      var ayah = sArgs[2]['ayah'];
      location.href = 'http://quran.com/' + sura + '/' + ayah;
   };
   this.autocomp.itemSelectEvent.subscribe(itemSelectHandler);

   // auto focus the search box on load
	document.getElementById('searchbox').focus();
};
