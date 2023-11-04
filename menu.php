<?php
require('setting.php');
$submenu = [];
foreach (explode(',', ROOX_MODULES) as $module) 
{
    $path = "plugins/".ROOX_PLUGIN."/modules/$module/menu.php";
    if(is_file($path))
    {
        require($path);
        $submenu[] = $menu;
    }
}
$app_plugin_menu['menu'][] = array('title'=>ucwords(ROOX_PLUGIN),'url'=>url_for(ROOX_PLUGIN),'class'=>'fa-rocket','submenu'=>$submenu);
?>