<?php
require('setting.php');
foreach (explode(',', ROOX_MODULES) as $module) 
{
    $module_class = ucwords(strtolower($module));
    $paths = [
        "plugins/".ROOX_PLUGIN."/classes/$module_class.php",
        "plugins/".ROOX_PLUGIN."/modules/$module/prepare.php",
        "plugins/".ROOX_PLUGIN."/modules/$module/menu.php"
    ];
    foreach ($paths as $path) 
    {
        if(is_file($path))
        {
            require($path);
        }
    }
}

?>
