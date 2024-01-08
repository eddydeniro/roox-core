<?php
$menu = [];
if($app_user['id']==1)
{
    $menu = ['title'=>TEXT_SETTINGS, 'url'=>url_for(ROOX_PLUGIN."/".$module."/"), 'class'=>'fa-gear'];
}
?>