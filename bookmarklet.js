var jl_overlay;

function jl_submit_url(url) {
    xmlHttp = new XMLHttpRequest();
    xmlHttp.open('GET', jl_url+'addlink.php?url='+escape(url)+'&'+jl_token, true);
    xmlHttp.send(null);
}

function jl_submit_current() {
    jl_submit_url(window.location.href);
}

function jl_createOverlay() {
    var d = document;
    jl_overlay = d.createElement('div');
    jl_overlay.setAttribute('style', 'position:absolute; top 10px; left: 10px; background-color: #fff;border: 2px solid black;');

    jl_overlay.innerHTML = ''
        +'<form action="" name="jl_tag_frm" onsubmit="return jl_submittags()">'
        +'<input type="text" name="tags"> <input type="submit">'
        +'</form>'
        ;

    d.getElementsByTagName('body').item(0).appendChild(jl_overlay);
}

function jl_submittags() {
    xmlHttp = new XMLHttpRequest();
    xmlHttp.open('GET', jl_url+'settag.php?url='+escape(window.location.href)+'&tas='+escape(document.jl_tag_frm.tags.value)+'&'+jl_token, true);
    xmlHttp.send(null);

    jl_overlay.parent.removeChild(jl_overlay);

    return false;
}

jl_submit_current();
jl_createOverlay();
