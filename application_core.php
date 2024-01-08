<?php
    //Plugin's application_core is accessed earlier than application_top.

    require 'setting.php';
    ${ROOX_PLUGIN . "_modules"} = ROOX_MODULES ? explode(',', ROOX_MODULES) : [];
    ${ROOX_PLUGIN . '_all_modules'} = array_merge(['core'], ${ROOX_PLUGIN . "_modules"});

    foreach (${ROOX_PLUGIN . '_all_modules'} as $module_name) 
    {
        $install = component_path(ROOX_PLUGIN."/{$module_name}/globals");
        if(is_file($install))
        {
            require $install;
        }
    }

    //Special treatment for modules modx
    //If it's not available, but previously installed
    if(!in_array('modx', ${ROOX_PLUGIN . "_modules"}) && is_file('index.php.bak'))
    {
        rename('index.php.bak', 'index.php');
    }
?>