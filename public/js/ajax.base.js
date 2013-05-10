/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var ajax = {
    create: function() {
        if (window.XMLHttpRequest) {
            oHttp = new XMLHttpRequest();
            return oHttp;
        }
        else if (window.ActiveXobject) {
            var versions = [
                "MSXML2.XmlHttp.6.0",
                "MSXML2.XmlHttp.3.0"
            ];
            for (var i = 0; i < versions.length; i++) {
                try {
                    oHttp = new ActiveXObject(versions[i]);
                    return oHttp;
                } catch (error) {
                }
            }
        }
        return null;
    },
    post: function(url, data, callback, sync) {
        if (!sync) {
            sync = true;
        }
        var oHttp = ajax.create();
        oHttp.open('POST', url, sync);
        oHttp.send(data);
        function request_onreadystatechange() {
            if (oHttp.readyState === 4 && oHttp.status === 200) {
                callback(oHttp.status, oHttp.responseText);
            }
        }
        oHttp.onreadystatechange = request_onreadystatechange;
    },
    get: function(url, callback, sync) {
        if (!sync) {
            sync = true;
        }
        var oHttp = ajax.create();
        oHttp.open('GET', url, sync);
        oHttp.send(null);
        function request_onreadystatechange() {
            if (oHttp.readyState === 4 && oHttp.status === 200) {
                callback(oHttp.status, oHttp.responseText);
            }
        }
        oHttp.onreadystatechange = request_onreadystatechange;
    },
    readState: function(int) {
        if (int === 1) {
            return this.oHttp.readyState;
        } else {
            return this.oHttp.state;
        }
    }
};


