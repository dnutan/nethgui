<?php

switch (intval($view['type']) & Nethgui_Core_DialogBox::MASK_SEVERITY) {
    case Nethgui_Core_DialogBox::NOTIFY_SUCCESS:
        $cssClass = 'Notification success ui-state-highlight';
        $icon = 'check';
        break;
    case Nethgui_Core_DialogBox::NOTIFY_WARNING:
        $cssClass = 'Notification warning ui-state-error';
        $icon = 'info';
        break;
    case Nethgui_Core_DialogBox::NOTIFY_ERROR:
        $cssClass = 'Notification error ui-state-error';
        $icon = 'alert';
        break;
}

?><div class="<?php echo $cssClass ?>" id="<?php  echo $view['dialogId']

    ?>"><span class='NotificationIcon ui-icon ui-icon-<?php echo $icon ?>' style='float: left; margin-right: .3em;'></span><span class="message"><?php
    echo $view['message']; ?></span><?php 

if(count($view['actions']) > 0):
    ?><ul class="buttonList"><?php
    foreach ($view['actions'] as $action) :
        ?><li><?php echo $action ?></li><?php
    endforeach;
    ?></ul><?php
endif;

?></div>