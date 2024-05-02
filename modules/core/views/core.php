<h3 class="page-title"><a href="<?php echo $module_url ?>"><?php echo ucwords(ROOX_PLUGIN) ?></a></h3>
<?php
    $active_tab = ${"{$plugin_name}_active_tab"};
    echo TEXT_VERSION." ".$module_version;
    echo "<p style='padding-top:15px;'>".TEXT_ROOX_INFO."</p>";    
?>
<hr>
<h4><?php echo TEXT_SETTINGS; ?></h4>

<div class="tabbable tabbable-custom">

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="<?php echo $active_tab == 'core' ? 'active' : ''; ?>"><a href="#general" aria-controls="general" role="tab" data-toggle="tab" class="tab_switcher" data-active="core"><?php echo TEXT_GENERAL ?></a></li>
    <?php
    $includes = [];
    $installed_modules = [];

    foreach (${"{$plugin_name}_modules"} as $module_name)
    {
        if(!$module_name)
        {
            continue;
        }
        $path = component_path("{$plugin_name}/{$module_name}/config");  
        if(is_file($path))
        {
            echo "<li role='presentation' class=".($active_tab == $module_name ? 'active' : '')."><a href='#{$module_name}' aria-controls='{$module_name}' role='tab' data-toggle='tab' class='tab_switcher' data-active='{$module_name}'>".ucwords($module_name)."</a></li>";
            $includes[$module_name] = $path;
        }        
        $module_top_path = "plugins/{$plugin_name}/modules/{$module_name}/module_top.php";
        if(is_file($module_top_path))
        {
            require $module_top_path;
            $module_info = str_replace(['{$plugin_name}','{$module_name}','{$module_version}'], [$plugin_name,$module_name,$module_version], $module_info);
            $installed_modules[] = ["name"=>ucwords($module_name), "title"=>$module_title, "version"=>$module_version, "description"=>$module_info, "url"=>$module_url];
        }
    }
    ?>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane fade <?php echo $active_tab == 'core' ? 'in active' : ''; ?>" id="general">
        <h5><u><b><?php echo TEXT_MODULE_INSTALLER; ?></b></u></h5>
        <p>
            <?php echo TEXT_MODULE_INSTALLER_INFO ?>
        </p>
        <?php
            echo form_tag('uploader', url_for("{$plugin_name}/core/"), ['enctype'=>"multipart/form-data"]);
            echo input_file_tag('module_upload');
            echo input_hidden_tag('action', 'install');
            echo '<br>';
            echo submit_tag('Install');
            echo '</form>';
        ?>
        <hr>
        <h5><u><b><?php echo TEXT_INSTALLED_MODULES; ?></b></u></h5>
        <div class="table-scrollable">
            <div class="table-scrollable table-wrapper slimScroll" id="slimScroll">
                <table class="tree-table table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo TEXT_ACTION ?></th>
                            <th><?php echo TEXT_NAME ?></th>
                            <th><?php echo TEXT_DESCRIPTION ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!count($installed_modules)):?>
                            <tr><td colspan="4"><?php echo TEXT_FIND_MODULES ?></td></tr>
                        <?php else: ?>
                            <?php foreach($installed_modules as $key=>$values): ?>
                            <tr>
                                <td><?php echo $key+1 ?></td>
                                <td>
                                    <a title="<?php echo TEXT_ACCESS ?>" class="btn btn-default btn-xs" href="#" onclick="open_dialog('<?php echo url_for("{$plugin_name}/core/access_form", "name=" . strtolower($values['name'])); ?>'); return false;">
                                        <i class="fa fa-users"></i>
                                    </a>
                                    <a title="<?php echo TEXT_REINSTALL?>" class="btn btn-default btn-xs" href="<?php echo url_for("{$plugin_name}/core/", "action=reinstall&name=" . strtolower($values['name'])); ?>" >
                                        <i class="fa fa-angle-double-down"></i>
                                    </a>
                                </td>
                                <td><?php echo ($values['url'] ? "<a href='{$values['url']}' >{$values['name']}</a>" : $values['name']) . '<br />' . $values['version']; ?></td>
                                <td><?php echo "<b>{$values['title']}</b><br/>{$values['description']}" ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div><?php echo "<b>" . TEXT_NOTE ."</b>: <br/>". TEXT_REINSTALL_INFO; ?></div>
        <?php 
            if(!in_array('lingua', ${ROOX_PLUGIN . "_modules"})): 
                echo "<hr>";
                $update_url = url_for("{$plugin_name}/core/");
                $add_url = url_for("{$plugin_name}/core/form", "active=core");
                require component_path("{$plugin_name}/core/definitions_form");
            endif; 
        ?>        
    </div>
    <?php
        foreach ($includes as $module_name=>$include) 
        {
            echo "<div role='tabpanel' class='tab-pane fade ".($active_tab == $module_name ? 'in active' : '')."' id='{$module_name}'>";
            require($include);
            echo "</div>";
        }
    ?>
  </div>

</div>

<script>
    $('.tab_switcher').click(function(){
        $.ajax({
            url: '<?php echo url_for("{$plugin_name}/core/"); ?>',
            method: 'post',
            data: {active: $(this).data('active')}
        })
    })
</script>