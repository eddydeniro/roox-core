<?php
global ${ROOX_PLUGIN . "_modules"};
foreach (${ROOX_PLUGIN . "_modules"} as $module) 
{
    $path = component_path(ROOX_PLUGIN . "/{$module}/header_menu");
    if(is_file($path))
    {
        require $path;
    }
}
?>