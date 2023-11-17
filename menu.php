<?php
$submenu = [];
foreach (array_merge(['core'], ${ROOX_PLUGIN . "_modules"}) as $module) 
{
    $path = component_path(ROOX_PLUGIN . "/{$module}/menu");
    
    if(is_file($path))
    {
        require $path;
        if(count($menu))
        {
            $submenu[] = $menu;
        }
    }
}
if(count($submenu))
{
    $app_plugin_menu['menu'][] = array('title'=>ucwords(ROOX_PLUGIN),'url'=>url_for(ROOX_PLUGIN),'class'=>'glyphicon glyphicon-tower text13', 'submenu'=>$submenu);
}
?>