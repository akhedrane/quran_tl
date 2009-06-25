<head>
    <title>quran auto-complete demo</title>

    <style type="text/css">
    body {
        margin: 0;
        padding: 0;
    }
    #autocomp {    
        width: 25em; 
        padding-bottom: 2em;
    }
    </style>
    
    <!-- combo-handled YUI CSS files: -->
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.7.0/build/fonts/fonts-min.css&2.7.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    
    <!-- combo-handled YUI JS files: -->
    <script type="text/javascript" src="http://yui.yahooapis.com/combo?2.7.0/build/yahoo-dom-event/yahoo-dom-event.js&2.7.0/build/animation/animation-min.js&2.7.0/build/connection/connection-min.js&2.7.0/build/datasource/datasource-min.js&2.7.0/build/autocomplete/autocomplete-min.js&2.7.0/build/json/json-min.js"></script>
</head>

<body class="yui-skin-sam">
    <h1>quran transliteration autocompletion demo</h1>
    <div class="intro">
        this is a proof of concept to illustrate a potential autocomplete solution for
        searching quranic transliteration.
    </div>
    
    <h3>search the quran:</h3>
    <div id="autocomp">   
        <input type="text" id="searchbox">
        <div id="searchcontainer"></div> 
    </div>

    <script type="text/javascript" src="suggestions.js"></script>
</body>