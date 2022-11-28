<?php

if (!empty($message)) {
	$html = '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
}else{
	$html = '';
}

echo $html;
?>
<div id="box_please_wait">
    <div class="box_msgbox">
        <h1><?php echo _("Updating, Please Wait..."); ?></h1>
        <div class="fa-5x box_spin">
            <i class="fa fa-spinner fa-pulse"></i>
        </div>
    </div>
</div>