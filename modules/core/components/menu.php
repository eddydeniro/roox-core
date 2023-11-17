<?php
$menu = [];
if(!$app_user['group_id'])
{
    $menu = ['title'=>TEXT_SETTINGS, 'url'=>url_for(ROOX_PLUGIN."/".$module."/"), 'class'=>'fa-gear'];
}
?>