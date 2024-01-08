<?php
if(app_session_is_registered('app_logged_users_id'))
{
    foreach (glob("plugins/".ROOX_PLUGIN."/functions/*") as $item) 
    {
        if(is_file($item))
        {
            require $item;    
        }
    }
    foreach (glob("plugins/".ROOX_PLUGIN."/classes/*") as $item) 
    {
        if(is_file($item))
        {
            require $item;    
        }
    }

    $Element = new Roox\Element();
        
    foreach (${ROOX_PLUGIN . '_all_modules'} as $module_name) 
    {
        $install = component_path(ROOX_PLUGIN."/{$module_name}/install");
        if(is_file($install))
        {
            require $install;
            rename($install, component_path(ROOX_PLUGIN."/{$module_name}/_install"));
        }
        $init = component_path(ROOX_PLUGIN . "/{$module_name}/init");
        if(is_file($init))
        {
            require $init;
        }
    }
}
?>