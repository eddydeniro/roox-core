<?php
if(!app_session_is_registered("{$plugin_name}_active_tab"))
{
    ${"{$plugin_name}_active_tab"} = 'core';
    app_session_register("{$plugin_name}_active_tab");
}
if(isset($_POST['active']))
{
    ${"{$plugin_name}_active_tab"} = $_POST['active'];
}
switch ($app_module_action) 
{
    case 'save_def':
        $id = Roox\Core::saveNewDefinition($_POST['dict_key'], $_POST['dict_value']);
        if($id)
        {
            $roox_dictionary[$id] = ['dict_key'=>$_POST['dict_key'], 'dict_value'=>$_POST['dict_value']];
            if(isset($Locale))
            {
                $Locale->saveDefinitions([$id=>$_POST['dict_value']]);
            }
        }        
        break;
    case 'check_def':
        $dict_key = $_POST['dict_key'];
        if(defined($dict_key))
        {
            echo 0;
            exit();
        }
        $q = db_query("SELECT COUNT(*) as num FROM {$dictionary_table} WHERE dict_key='{$dict_key}'");
        $d = db_fetch_array($q);
        echo !(int)$d['num'] ? 1 : 0;
        exit();
    case 'update_def':
        if(isset($Locale))
        {
            $Locale->saveDefinitions($_POST['definitions']);
            $roox_dictionary = $Locale->getDefinitions();            
        }
        else
        {
            $sql_data = [];
            foreach ($_POST['definitions'] as $id => $dict_value) 
            {
                if(!$dict_value)
                {
                    continue;
                }
                $sql_data[] = "($id, '{$dict_value}')";
            }
    
            $fields_query = "INSERT INTO {$dictionary_table} (`id`, `dict_value`) VALUES " . implode(",", $sql_data) . " ON DUPLICATE KEY UPDATE `dict_value`=VALUES(`dict_value`)";
            db_query($fields_query);    
        }
        break;
    case 'install':
        $install = Roox\Core::installModule($_FILES['module_upload']);
        $message = $install['success'] ? TEXT_MODULE_IS_INSTALLED : TEXT_MODULE_INSTALL_FAILED;
        $type = $install['success'] ? 'success' : 'danger';
        ${ROOX_PLUGIN.'_alert'} = [$message, $type];
        redirect_to("{$plugin_name}/core/");

    case 'reinstall':
        $install = component_path(ROOX_PLUGIN."/{$_GET['name']}/_install");
        if(is_file($install))
        {
            $module_name = $_GET['name'];
            require $install;
        }
        redirect_to("{$plugin_name}/core/");        
        exit();
    case 'save_access':
        $id = (int)$_POST['id'];
        $sql_data = [
            'name'=>$_POST['name'],
            'users_id'=>implode(",", $_POST['users_id'] ?? []),
            'groups_id'=>implode(",", $_POST['groups_id'] ?? [])
        ];
        db_perform(${ROOX_PLUGIN . '_modules_table'}, $sql_data, $id ? 'update' : 'insert', "id={$id}");
        redirect_to("{$plugin_name}/core/");
}
?>