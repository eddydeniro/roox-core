<?php 
require("plugins/".ROOX_PLUGIN."/setting.php");
foreach (explode(',', ROOX_MODULES) as $module) 
{
    $path = "plugins/".ROOX_PLUGIN."/modules/$module/head.php";
    if(is_file($path))
    {
        require($path);
    }
}
?>
