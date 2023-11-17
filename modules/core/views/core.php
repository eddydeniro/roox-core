<h3 class="page-title"><a href="<?php echo $module_url ?>"><?php echo ucwords(ROOX_PLUGIN) ?></a></h3>
<?php
    echo TEXT_VERSION." ".$module_version;
    echo "<p style='padding-top:15px;'>".TEXT_ROOX_INFO."</p>";    
    $active_tab = $_POST['active'] ? $_POST['active'] : ($_GET['active'] ? $_GET['active'] : 'core');
?>
<hr>
<h4><?php echo TEXT_SETTINGS; ?></h4>

<div class="tabbable tabbable-custom">

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="<?php echo $active_tab == 'core' ? 'active' : ''; ?>"><a href="#general" aria-controls="general" role="tab" data-toggle="tab">General</a></li>
    <?php
    $includes = [];
    $installed_modules = [];

    foreach (${ROOX_PLUGIN . "_modules"} as $module_name)
    {
        if(!$module_name)
        {
            continue;
        }
        $path = component_path(ROOX_PLUGIN . "/{$module_name}/config");  
        if(is_file($path))
        {
            echo "<li role='presentation' class=".($active_tab == $module_name ? 'active' : '')."><a href='#{$module_name}' aria-controls='{$module_name}' role='tab' data-toggle='tab'>".ucwords($module_name)."</a></li>";
            $includes[$module_name] = $path;
        }        
        $module_top_path = "plugins/" . ROOX_PLUGIN . "/modules/{$module_name}/module_top.php";
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
            echo form_tag('uploader', url_for(ROOX_PLUGIN . "/core/"), ['enctype'=>"multipart/form-data"]);
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
                            <th><?php echo TEXT_NAME ?></th>
                            <th><?php echo TEXT_VERSION ?></th>                            
                            <th style="width:80%;"><?php echo TEXT_DESCRIPTION ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!count($installed_modules)):?>
                            <tr><td colspan="4"><?php echo TEXT_FIND_MODULES ?></td></tr>
                        <?php else: ?>
                            <?php foreach($installed_modules as $key=>$values): ?>
                            <tr>
                                <td><?php echo $key+1 ?></td>
                                <td><a href="<?php echo $values['url'] ?>"><?php echo $values['name'] ?></a></td>
                                <td><?php echo $values['version'] ?></td>
                                <td><?php echo "<b>{$values['title']}</b><br/>{$values['description']}" ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
                                
        <?php
            if(!in_array('locale', ${ROOX_PLUGIN . "_modules"}))
            {
                echo "<hr>";
                $update_url = url_for(ROOX_PLUGIN . "/core/");
                $add_url = url_for(ROOX_PLUGIN . "/core/form", "active=core");
                require component_path(ROOX_PLUGIN . "/core/definitions_form");    
            }
        ?>

    </div>
    <?php
        foreach ($includes as $module_name=>$include) 
        {
            echo "<div role='tabpanel' class='tab-pane fade ".($active_tab == $module_name ? 'in active' : '')."' id='{$module_name}'>";
            require $include;
            echo "</div>";
        }
        // $migrator = new Roox\Migrator([21]);
        // print_rr($migrator->getData());
    ?>
  </div>

</div>