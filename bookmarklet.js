/*
For using this add a bookmark similar to:

javascript:(function(){jl_url='http://schlueters.de/links/';jl_token='TOKEN';var d=document;if(!d.getElementById("jl_overlay")){var s=d.createElement('script');s.src=jl_url+"bookmarklet.js";s.id="jl_overlay";d.body.appendChild(s);}})();

You should at least change jl_url and jl_token there, set jl_token to a 
url-encoded name=value pair which you check in auth.php

*/

function JohannesLinks(url, token)
{
    this.URL   = url;
    this.Token = token;
    this.createOverlay();
    this.submitCurrent();
}

JohannesLinks.prototype.URL     = "";
JohannesLinks.prototype.Token   = "";
JohannesLinks.prototype.Overlay = null;

JohannesLinks.prototype.submitURL = function(url) {
    window.jl_iframe.location.href = this.URL+'addlink.php?url='+escape(url)+'&'+this.Token;
}

JohannesLinks.prototype.submitCurrent = function() {
    this.submitURL(window.location.href);
}

JohannesLinks.prototype.createOverlay = function() {
    var d = document;
    this.Overlay = d.createElement('div');
    this.Overlay.setAttribute('style', 'position:absolute; top 10px; left: 10px; background-color: #fff;border: 2px solid black;');

    this.Overlay.innerHTML = ''
        +'<form action="" name="jl_tag_frm" onsubmit="return jl.submitTags()">'
        +'<input type="text" name="tags"> <input type="submit">'
        +'</form>'
        +'<iframe style="display:none;" name="jl_iframe"></iframe>'
        ;

    d.getElementsByTagName('body').item(0).appendChild(this.Overlay);
}

JohannesLinks.prototype.submitTags = function() {
    window.jl_iframe.location.href = this.URL
                                   + 'settag.php?url='
                                   + escape(window.location.href)
                                   + '&tags='
                                   + escape(document.jl_tag_frm.tags.value)
                                   + '&'
                                   + this.Token;

    this.Overlay.style.display = "none";

    return false;
}

jl = new JohannesLinks(jl_url, jl_token); 

