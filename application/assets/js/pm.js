var NO_JQUERY={};(function(b,d,e){if(!("console" in b)){var f=b.console={};f.log=f.warn=f.error=f.debug=function(){}}if(d===NO_JQUERY){d={fn:{},extend:function(){var h=arguments[0];for(var j=1,g=arguments.length;j<g;j++){var c=arguments[j];for(var k in c){h[k]=c[k]}}return h}}}d.fn.pm=function(){console.log("usage: \nto send:    $.pm(options)\nto receive: $.pm.bind(type, fn, [origin])");return this};d.pm=b.pm=function(c){a.send(c)};d.pm.bind=b.pm.bind=function(h,g,c,i){a.bind(h,g,c,i)};d.pm.unbind=b.pm.unbind=function(g,c){a.unbind(g,c)};d.pm.origin=b.pm.origin=null;d.pm.poll=b.pm.poll=200;var a={send:function(c){var i=d.extend({},a.defaults,c),g=i.target;if(!i.target){console.warn("postmessage target window required");return}if(!i.type){console.warn("postmessage type required");return}var h={data:i.data,type:i.type};if(i.success){h.callback=a._callback(i.success)}if(i.error){h.errback=a._callback(i.error)}if(("postMessage" in g)&&!i.hash){a._bind();g.postMessage(JSON.stringify(h),i.origin||"*")}else{a.hash._bind();a.hash.send(i,h)}},bind:function(j,i,g,k){if(("postMessage" in b)&&!k){a._bind()}else{a.hash._bind()}var c=a.data("listeners.postmessage");if(!c){c={};a.data("listeners.postmessage",c)}var h=c[j];if(!h){h=[];c[j]=h}h.push({fn:i,origin:g||d.pm.origin})},unbind:function(p,n){var h=a.data("listeners.postmessage");if(h){if(p){if(n){var k=h[p];if(k){var g=[];for(var j=0,c=k.length;j<c;j++){var q=k[j];if(q.fn!==n){g.push(q)}}h[p]=g}}else{delete h[p]}}else{for(var j in h){delete h[j]}}}},data:function(g,c){if(c===e){return a._data[g]}a._data[g]=c;return c},_data:{},_CHARS:"0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz".split(""),_random:function(){var g=[];for(var c=0;c<32;c++){g[c]=a._CHARS[0|Math.random()*32]}return g.join("")},_callback:function(g){var c=a.data("callbacks.postmessage");if(!c){c={};a.data("callbacks.postmessage",c)}var h=a._random();c[h]=g;return h},_bind:function(){if(!a.data("listening.postmessage")){if(b.addEventListener){b.addEventListener("message",a._dispatch,false)}else{if(b.attachEvent){b.attachEvent("onmessage",a._dispatch)}}a.data("listening.postmessage",1)}},_dispatch:function(q){try{var h=JSON.parse(q.data)}catch(s){console.warn("postmessage data invalid json: ",s);return}if(!h.type){console.warn("postmessage message type required");return}var n=a.data("callbacks.postmessage")||{},k=n[h.type];if(k){k(h.data)}else{var j=a.data("listeners.postmessage")||{};var u=j[h.type]||[];for(var m=0,p=u.length;m<p;m++){var g=u[m];if(g.origin&&g.origin!="*"&&q.origin!==g.origin){console.warn("postmessage message origin mismatch",q.origin,g.origin);if(h.errback){var t={message:"postmessage origin mismatch",origin:[q.origin,g.origin]};a.send({target:q.source,data:t,type:h.errback})}continue}try{var c=g.fn(h.data);if(h.callback){a.send({target:q.source,data:c,type:h.callback})}}catch(s){if(h.errback){a.send({target:q.source,data:s,type:h.errback})}}}}}};a.hash={send:function(r,c){var o=r.target,g=r.url;if(!g){console.warn("postmessage target window url is required");return}g=a.hash._url(g);var h,q=a.hash._url(b.location.href);if(b==o.parent){h="parent"}else{try{for(var j=0,l=parent.frames.length;j<l;j++){var k=parent.frames[j];if(k==b){h=j;break}}}catch(m){h=b.name}}if(h==null){console.warn("postmessage windows must be direct parent/child windows and the child must be available through the parent window.frames list");return}var n={"x-requested-with":"postmessage",source:{name:h,url:q},postmessage:c};var p="#x-postmessage-id="+a._random();o.location=g+p+encodeURIComponent(JSON.stringify(n))},_regex:/^\#x\-postmessage\-id\=(\w{32})/,_regex_len:"#x-postmessage-id=".length+32,_bind:function(){if(!a.data("polling.postmessage")){setInterval(function(){var g=""+b.location.hash,c=a.hash._regex.exec(g);if(c){var h=c[1];if(a.hash._last!==h){a.hash._last=h;a.hash._dispatch(g.substring(a.hash._regex_len))}}},d.pm.poll||200);a.data("polling.postmessage",1)}},_dispatch:function(p){if(!p){return}try{p=JSON.parse(decodeURIComponent(p));if(!(p["x-requested-with"]==="postmessage"&&p.source&&p.source.name!=null&&p.source.url&&p.postmessage)){return}}catch(t){return}var h=p.postmessage,q=a.data("callbacks.postmessage")||{},k=q[h.type];if(k){k(h.data)}else{var m;if(p.source.name==="parent"){m=b.parent}else{m=b.frames[p.source.name]}var j=a.data("listeners.postmessage")||{};var w=j[h.type]||[];for(var n=0,s=w.length;n<s;n++){var g=w[n];if(g.origin){var v=/https?\:\/\/[^\/]*/.exec(p.source.url)[0];if(v!==g.origin){console.warn("postmessage message origin mismatch",v,g.origin);if(h.errback){var u={message:"postmessage origin mismatch",origin:[v,g.origin]};a.send({target:m,data:u,type:h.errback,hash:true,url:p.source.url})}continue}}try{var c=g.fn(h.data);if(h.callback){a.send({target:m,data:c,type:h.callback,hash:true,url:p.source.url})}}catch(t){if(h.errback){a.send({target:m,data:t,type:h.errback,hash:true,url:p.source.url})}}}}},_url:function(c){return(""+c).replace(/#.*$/,"")}};d.extend(a,{defaults:{target:null,url:null,type:null,data:null,success:null,error:null,origin:"*",hash:false}})})(this,typeof jQuery==="undefined"?NO_JQUERY:jQuery);if(!("JSON" in window&&window.JSON)){JSON={}}(function(){function f(n){return n<10?"0"+n:n}if(typeof Date.prototype.toJSON!=="function"){Date.prototype.toJSON=function(key){return this.getUTCFullYear()+"-"+f(this.getUTCMonth()+1)+"-"+f(this.getUTCDate())+"T"+f(this.getUTCHours())+":"+f(this.getUTCMinutes())+":"+f(this.getUTCSeconds())+"Z"};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf()}}var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={"\b":"\\b","\t":"\\t","\n":"\\n","\f":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},rep;function quote(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==="string"?c:"\\u"+("0000"+a.charCodeAt(0).toString(16)).slice(-4)})+'"':'"'+string+'"'}function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==="object"&&typeof value.toJSON==="function"){value=value.toJSON(key)}if(typeof rep==="function"){value=rep.call(holder,key,value)}switch(typeof value){case"string":return quote(value);case"number":return isFinite(value)?String(value):"null";case"boolean":case"null":return String(value);case"object":if(!value){return"null"}gap+=indent;partial=[];if(Object.prototype.toString.apply(value)==="[object Array]"){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||"null"}v=partial.length===0?"[]":gap?"[\n"+gap+partial.join(",\n"+gap)+"\n"+mind+"]":"["+partial.join(",")+"]";gap=mind;return v}if(rep&&typeof rep==="object"){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==="string"){v=str(k,value);if(v){partial.push(quote(k)+(gap?": ":":")+v)}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?": ":":")+v)}}}}v=partial.length===0?"{}":gap?"{\n"+gap+partial.join(",\n"+gap)+"\n"+mind+"}":"{"+partial.join(",")+"}";gap=mind;return v}}if(typeof JSON.stringify!=="function"){JSON.stringify=function(value,replacer,space){var i;gap="";indent="";if(typeof space==="number"){for(i=0;i<space;i+=1){indent+=" "}}else{if(typeof space==="string"){indent=space}}rep=replacer;if(replacer&&typeof replacer!=="function"&&(typeof replacer!=="object"||typeof replacer.length!=="number")){throw new Error("JSON.stringify")}return str("",{"":value})}}if(typeof JSON.parse!=="function"){JSON.parse=function(text,reviver){var j;function walk(holder,key){var k,v,value=holder[key];if(value&&typeof value==="object"){for(k in value){if(Object.hasOwnProperty.call(value,k)){v=walk(value,k);if(v!==undefined){value[k]=v}else{delete value[k]}}}}return reviver.call(holder,key,value)}cx.lastIndex=0;if(cx.test(text)){text=text.replace(cx,function(a){return"\\u"+("0000"+a.charCodeAt(0).toString(16)).slice(-4)})}if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,""))){j=eval("("+text+")");return typeof reviver==="function"?walk({"":j},""):j}throw new SyntaxError("JSON.parse")}}}());