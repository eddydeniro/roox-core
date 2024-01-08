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
    let dict_key_val = "";
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
    };
    roox.selectAll = function (){
        $('.select_all').click(function(){
            //data.target should comprises of parent's Id (always started with pnt) and children class part
            //for example: data.target = 'pntClassName_childClassName'
            //This means that the id of parent should be '#pntClassName'
            //and the class of the children is '.pntClassName_childClassName'
            const checkStat = this.checked, classTarget = $(this).data('target');
            $('.'+classTarget).each(function(){
                $(this).prop('checked', checkStat);
            });
            $.uniform.update();
        });
        $('.select_one').click(function(){
            const classListArray = $(this).attr('class').split(/\s+/);
            let primaryClass = '';
            $.each(classListArray, function(index, item){
                if (!primaryClass && item.substr(0,3) == 'pnt'){
                    primaryClass = item;
                }
            });
            const arr = primaryClass.split('_');
            const parentId = '#'+arr[0];
            if(parentId){
                $(parentId).prop('checked', $('.'+primaryClass).length == $('.'+primaryClass+':checked').length);
            }
            $.uniform.update();
        });    
    };    
    roox.waitForElement = function (selector) {
        return new Promise(resolve => {
            if (document.querySelector(selector)) {
                return resolve(document.querySelector(selector));
            }

            const observer = new MutationObserver(mutations => {
                if (document.querySelector(selector)) {
                    resolve(document.querySelector(selector));
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

</script>
<?php 
global ${ROOX_PLUGIN . '_all_modules'};

if(!app_session_is_registered(ROOX_PLUGIN . "_alert"))
{
    ${ROOX_PLUGIN . "_alert"} = [];
    app_session_register(ROOX_PLUGIN . "_alert");
}

foreach (${ROOX_PLUGIN . '_all_modules'} as $module) 
{
    $path = component_path(ROOX_PLUGIN . "/{$module}/head");  
    if(is_file($path))
    {
        require $path;
    }
}
?>