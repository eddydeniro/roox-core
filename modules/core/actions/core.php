<?php
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
        redirect_to("{$plugin_name}/{$module_name}/", "active=" . $_POST['active']);
    case 'install':
        /*
        $file = $_FILES['module_upload'];

        if($file['type']=='application/x-zip-compressed')
        {
            $zip = new ZipArchive;
            $res = $zip->open($file['tmp_name']);
            $module_installed = "";
            $module_path = "";
            $check = 0;
            if($res === TRUE)
            {
                $destination = "plugins/".ROOX_PLUGIN;
                $tmpDir = "{$destination}/tmp_" . uniqid();
                mkdir($tmpDir, 0777, true);
                $filesExtracted = [];
                $specFile = [];
                $currentInstall = ${ROOX_PLUGIN . "_modules"};

                for($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    // Skip .git folder
                    if(strpos($name, '.git')!==false)
                    {
                        continue;
                    }
            
                    if($check<2 && strpos($name, 'modules')!==false)
                    {
                        $check++;
                        if($check==1)
                        {
                            $module_path = $name;    
                        }
                        if($check==2)
                        {
                            $module_installed = substr($name, strlen($module_path), -1);
                        }
                    }

                    list($firstFolder, $targetPath) = explode("/", $name, 2);
                    $targetFile = "{$destination}/{$targetPath}";

                    // Create the directories if necessary
                    $dir = dirname($targetFile);
                    if (!is_dir($dir))
                    {
                        mkdir($dir, 0777, true);
                    } 

                    if(substr($targetFile, strlen($targetFile)-1, 1)=="/")
                    {
                        continue;
                    }

                    if(in_array(substr($name, -7), ['LICENSE', 'ADME.md']))
                    {
                        $targetFile = "{$tmpDir}/".basename($name);
                        $specFile[] = $targetFile;
                    } 
                    else
                    {
                        $filesExtracted[] = $targetFile;
                    }
                    $fpr = $zip->getStream($name);
                    $fpw = fopen($targetFile, 'w');
                    while ($data = fread($fpr, 1024)) {
                        fwrite($fpw, $data);
                    }
                    fclose($fpr);
                    fclose($fpw);
                }
                $zip->close();
                $module_path = "{$destination}/modules/{$module_installed}/";
                if(count($specFile))
                {
                    foreach ($specFile as $from) 
                    {
                        $filesExtracted[] = $to = $module_path.basename($from);
                        copy($from, $to);
                    }
                }
                Roox\Core::deleteDir($tmpDir);
                if(!in_array($module_installed, $currentInstall))
                {
                    array_push($currentInstall, $module_installed); 
                    $script = "<?php \n\tdefine('ROOX_PLUGIN', '".ROOX_PLUGIN."'); \n\tdefine('ROOX_MODULES', '".implode(",", $currentInstall)."'); \n?>";
                    file_put_contents("plugins/".ROOX_PLUGIN."/setting.php", $script);    
                }
                
                $install_notes = strtoupper(ROOX_PLUGIN) . "\n";
                $install_notes .= "Module: $module_installed\n";
                $install_notes .= "Installed on ".date("Y-m-d", time())."\n\n";
                $install_notes .= "Installation files:\n";

                foreach($filesExtracted as $n=>$path)
                {
                    $install_notes .= ($n + 1).". {$path}\n";
                }
                $install_notes .= ($n + 2).". {$module_path}install_note.txt";

                file_put_contents($module_path."install_note.txt", $install_notes);

                $alerts->add(TEXT_MODULE_IS_INSTALLED, 'success');
            }
            else
            {
                $alerts->add(TEXT_MODULE_INSTALL_FAILED,'error');
            }
        }
        */
        $install = Roox\Core::installModule($_FILES['module_upload']);
        if($install['success'])
        {
            Roox\Core::alert(TEXT_MODULE ." ".$install['module_name']."<br />".TEXT_MODULE_IS_INSTALLED, TEXT_INFO, 'success');
            //$alerts->add(TEXT_MODULE_IS_INSTALLED, 'success');
        }
        else
        {
            Roox\Core::alert(TEXT_MODULE_INSTALL_FAILED, TEXT_INFO, 'error');
            //$alerts->add(TEXT_MODULE_INSTALL_FAILED,'error');
        }
        break;
}
// $filecontent = file_get_contents("plugins/roox/application_top.php");
// file_put_contents("plugins/roox/application_top_bak.php", $filecontent);
// highlight_string($filecontent);

?>