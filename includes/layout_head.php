<style>
    .transparent{
        border: none;
        background: none;
    }
    .input_adj{
        display: inline-block;
    }
    .text13{
        font-size: 13px !important;
    }
    .roox-alert{
        position: absolute;
        top: 50px;
        right: 0px;
        min-width: 200px;
        max-width: 500px;
    }
    .roox-alert > .title {
        font-weight: bold;
    }
</style>
<script>
    const roox = {};
    roox.alert = function(html, title = '<?php echo TEXT_INFO; ?>', classBg = 'success', duration = 5000){
        const element = $("<div class='alert alert-"+classBg+" roox-alert' role='alert'><div class='title'>"+title+"</div>"+html+"</div>").appendTo($('.header.navbar'));
        setTimeout(function(){
            const fadeTime = 500;
            const slideTime = 500;
            let opacity = 1;
            const fadeInterval = setInterval(function () {
                if (opacity > 0) {
                    opacity -= 0.1;
                    element.css('opacity', opacity);
                } else {
                    clearInterval(fadeInterval);
                    let height = element[0].offsetHeight;
                    const slideInterval = setInterval(function () {
                        if (height > 0) {
                            height -= 10;
                            element.css('height', height);
                        } else {
                            clearInterval(slideInterval);
                            element.remove();
                        }
                    }, slideTime / 10);
                }
            }, fadeTime / 10);
        }, duration);
    }
</script>
<?php 
global ${ROOX_PLUGIN . "_modules"};
foreach (${ROOX_PLUGIN . "_modules"} as $module) 
{
    $path = component_path(ROOX_PLUGIN . "/{$module}/head");  
    if(is_file($path))
    {
        require $path;
    }
}
?>