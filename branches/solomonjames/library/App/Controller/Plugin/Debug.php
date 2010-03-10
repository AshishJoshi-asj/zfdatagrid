<?php

class App_Controller_Plugin_Debug extends ZFDebug_Controller_Plugin_Debug
{
    protected function _headerOutput() {
        $collapsed = isset($_COOKIE['ZFDebugCollapsed']) ? $_COOKIE['ZFDebugCollapsed'] : 0;

        return ('
            <style type="text/css" media="screen">
                #ZFDebug_debug { font: 11px/1.4em Lucida Grande, Lucida Sans Unicode, sans-serif; position:fixed; bottom:5px; left:5px; color:#000; z-index: ' . $this->_options['z-index'] . ';}
                #ZFDebug_debug ol {margin:10px 0px; padding:0 25px}
                #ZFDebug_debug li {margin:0 0 10px 0;}
                #ZFDebug_debug .clickable {cursor:pointer}
                #ZFDebug_toggler { font-weight:bold; background:#BFBFBF; }
                .ZFDebug_span { border: 1px solid #999; border-right:0px; background:#DFDFDF; padding: 5px 5px; }
                .ZFDebug_last { border: 1px solid #999; }
                .ZFDebug_panel { text-align:left; position:absolute;bottom:21px;width:600px; max-height:400px; overflow:auto; display:none; background:#E8E8E8; padding:5px; border: 1px solid #999; }
                .ZFDebug_panel .pre {font: 11px/1.4em Monaco, Lucida Console, monospace; margin:0 0 0 22px}
                #ZFDebug_exception { border:1px solid #CD0A0A;display: block; }
            </style>
            <script type="text/javascript" charset="utf-8">
                if (typeof zfdebug == "undefined") zfdebug = {};

                zfdebug.autoloadjQuery = false;

                zfdebug.display = function() {
                    // Library isnt done loading
                    if (typeof(jQuery) == "undefined" || jQuery("*") === null) {
                        if (!zfdebug.autoloadjQuery) {
                            var scriptObj = document.createElement("script");
                            scriptObj.src = "'.$this->_options['jquery_path'].'";
                            scriptObj.type = "text/javascript";
                            var head=document.getElementsByTagName("head")[0];
                            head.insertBefore(scriptObj,head.firstChild);

                            zfdebug.autoloadjQuery = true;
                        }

                        setTimeout(zfdebug.display, 100);
                        return;
                    }

                    ZFDebugCollapsed();

                    return false;
                };

                window.load = zfdebug.display();

                function ZFDebugCollapsed() {
                    if ('.$collapsed.' == 1) {
                        ZFDebugPanel();
                        jQuery("#ZFDebug_toggler").html("&#187;");
                        return jQuery("#ZFDebug_debug").css("left", "-"+parseInt(jQuery("#ZFDebug_debug").outerWidth()-jQuery("#ZFDebug_toggler").outerWidth()+1)+"px");
                    }
                }

                function ZFDebugPanel(name) {
                    jQuery(".ZFDebug_panel").each(function(i){
                        if(jQuery(this).css("display") == "block") {
                            jQuery(this).slideUp();
                        } else {
                            if (jQuery(this).attr("id") == name)
                                jQuery(this).slideDown();
                            else
                                jQuery(this).slideUp();
                        }
                    });
                }

                function ZFDebugSlideBar() {
                    if (jQuery("#ZFDebug_debug").position().left > 0) {
                        document.cookie = "ZFDebugCollapsed=1;expires=;path=/";
                        ZFDebugPanel();
                        jQuery("#ZFDebug_toggler").html("&#187;");
                        return jQuery("#ZFDebug_debug").animate({left:"-"+parseInt(jQuery("#ZFDebug_debug").outerWidth()-jQuery("#ZFDebug_toggler").outerWidth()+1)+"px"}, "normal", "swing");
                    } else {
                        document.cookie = "ZFDebugCollapsed=0;expires=;path=/";
                        jQuery("#ZFDebug_toggler").html("&#171;");
                        return jQuery("#ZFDebug_debug").animate({left:"5px"}, "normal", "swing");
                    }
                }

                function ZFDebugToggleElement(name, whenHidden, whenVisible){
                    if(jQuery(name).css("display")=="none"){
                        jQuery(whenVisible).show();
                        jQuery(whenHidden).hide();
                    } else {
                        jQuery(whenVisible).hide();
                        jQuery(whenHidden).show();
                    }
                    jQuery(name).slideToggle();
                }
            </script>');
    }

    /**
     * Appends Debug Bar html output to the original page
     *
     * @param string $html
     * @return void
     */
    protected function _output($html)
    {
        $response = $this->getResponse();
        $response->setBody(preg_replace('/(<\/head>)/i', $this->_headerOutput().'$1', $response->getBody()));
        $response->setBody(str_ireplace('</body>', '<div id="ZFDebug_debug">'.$html.'</div></body>', $response->getBody()));
    }
}
