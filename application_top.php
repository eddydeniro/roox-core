<?php
require 'setting.php';
${ROOX_PLUGIN . "_modules"} = explode(',', ROOX_MODULES);

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
$dictionary_table = ROOX_PLUGIN . "_dictionary";
$file = component_path(ROOX_PLUGIN."/core/install");
if(is_file($file))
{
    require $file;
    rename($file, component_path(ROOX_PLUGIN."/core/_install"));
}
require component_path(ROOX_PLUGIN . "/core/init");
foreach (${ROOX_PLUGIN . "_modules"} as $module_name) 
{
    $init = component_path(ROOX_PLUGIN . "/{$module_name}/init");
    if(is_file($init))
    {
        require $init;
    }
}
?>
