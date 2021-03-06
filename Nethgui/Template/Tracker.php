<?php

/* @var $view \Nethgui\Renderer\Xhtml  */
$trackerStateTarget = $view->getClientEventTarget('trackerState');

echo  $view->panel()->setAttribute('receiver', '')->setAttribute('class', $trackerStateTarget)
    ->insert($view->progressBar('progress'))
    ->insert($view->textLabel('message')->setAttribute('class', 'wspreline')->setAttribute('tag', 'div'));

$messageTarget = $view->getClientEventTarget('message');

$view->includeCss("
.trackerError dt { margin-top: .2em}
.trackerError dd { margin: 0 0 0 1em }
.${messageTarget} { min-height: 2.5em }
");

$closeLabel = json_encode($view->translate("Close_label"));

$view->includeJavascript("
jQuery(document).ready(function($) {

    var tid;  // the timeout id
    var xhr;  // the last ajax request

    var updateDialog = function(value) {
        $('body > .ui-widget-overlay').css('cursor', value.action == 'open' ? 'progress' : 'auto');

        if(value.action) {
            $(this).dialog(value.action);
        }

        if(value.title) {
            $(this).dialog('option', 'title', value.title);
        }
    };

    var updateLocation = function(value) {
        if( ! value.url) {
            return;
        }
        var sendQuery = function() {
            xhr = $.Nethgui.Server.ajaxMessage({
                url: value.url,
                freezeElement: value.freeze ? $('#Tracker') : false
            });
            if( value.show ) {
                xhr.done(function () {
                    $('#' + value.show).trigger('nethguishow');
                });
            }
        };
        if(value.sleep > 0) {
            tid = window.setTimeout(sendQuery, value.sleep);
        } else {
            tid = false; sendQuery();
        }
    };

    $('#Tracker').dialog({
        autoOpen: false,
        closeOnEscape: false,
        modal: true,
        dialogClass: 'trackerDialog',
        buttons: {
            $closeLabel: function () {
                $(this).dialog('close');
                if(tid) {
                    window.clearTimeout(tid);
                }
                tid = false;
                if(xhr) {
                    try {
                        xhr.abort();
                    } catch (e) {
                        //pass
                    }
                }
                xhr = false;
            }
        }
    }).on('nethguiupdateview', function (e, value, selector) {
        if( ! $.isPlainObject(value)) {
            $(this).dialog('close');
            return;
        }
        if($.isPlainObject(value.dialog)) {
            updateDialog.call(this, value.dialog);
        }
        if($.isPlainObject(value.location)) {
            updateLocation.call(this, value.location);
        }
    }).Component();
});");
