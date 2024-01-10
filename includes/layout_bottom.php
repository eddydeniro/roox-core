<script>
    $(document).ready(function(){
        roox.selectAll();
    });
</script>
<?php
global ${ROOX_PLUGIN . '_all_modules'}, ${ROOX_PLUGIN.'_alert'};
foreach (${ROOX_PLUGIN . '_all_modules'} as $module) 
{
    $path = component_path(ROOX_PLUGIN . "/{$module}/bottom");
    if(is_file($path))
    {
        require $path;
    }
}
if(isset(${ROOX_PLUGIN.'_alert'}) && count(${ROOX_PLUGIN.'_alert'}))
{
    list($message, $type) = ${ROOX_PLUGIN.'_alert'};
    echo "<script>";
    echo "roox.alert('{$message}', '".TEXT_INFO."', '".ucwords($type)."');";
    echo "</script>";
    ${ROOX_PLUGIN.'_alert'} = [];
}
?>