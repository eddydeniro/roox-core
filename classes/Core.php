<?php
namespace Roox;

use ZipArchive;

class Core {
    static function rCopy($fromDir, $toDir, $childFolder = '')
    {
        $directory = opendir($fromDir);
    
        if (is_dir($toDir) === false) {
            mkdir($toDir);
        }
    
        if ($childFolder !== '') {
            if (is_dir("$toDir/$childFolder") === false) {
                mkdir("$toDir/$childFolder");
            }
    
            while (($file = readdir($directory)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
    
                if (is_dir("$fromDir/$file") === true) {
                    self::rCopy("$fromDir/$file", "$toDir/$childFolder/$file");
                } else {
                    copy("$fromDir/$file", "$toDir/$childFolder/$file");
                }
            }
    
            closedir($directory);
    
            return;
        }
        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_dir("$fromDir/$file") === true) {
                self::rCopy("$fromDir/$file", "$toDir/$file");
            }
            else {
                copy("$fromDir/$file", "$toDir/$file");
            }
        }
    
        closedir($directory);
    }

    static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    static function saveNewDefinition($key, $value)
    {
        if(!$value)
        {
            return false;
        }
        $key = trim($key);
        if(defined($key))
        {
            return false;
        }
        db_query("INSERT INTO ".ROOX_PLUGIN."_dictionary (`dict_key`, `dict_value`) VALUES ('{$key}', '{$value}')");
        return db_insert_id();
    }

    static function installModule($inputFile)
    {
        $inputFile = $_FILES['module_upload'];

        if($inputFile['type']=='application/x-zip-compressed')
        {
            $zip = new ZipArchive;
            $res = $zip->open($inputFile['tmp_name']);
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
                $installFile = $module_path."install.php";
                if(is_file($installFile))
                {
                    require $installFile;
                }
                if(count($specFile))
                {
                    foreach ($specFile as $from) 
                    {
                        $filesExtracted[] = $to = $module_path.basename($from);
                        copy($from, $to);
                    }
                }
                self::deleteDir($tmpDir);
                if(!in_array($module_installed, $currentInstall))
                {
                    array_push($currentInstall, $module_installed); 
                    $script = "<?php\n\tdefine('ROOX_PLUGIN', '".ROOX_PLUGIN."');\n\tdefine('ROOX_MODULES', '".implode(",", $currentInstall)."');\n?>";
                    file_put_contents("plugins/".ROOX_PLUGIN."/setting.php", $script);    
                }    
                
                $install_notes = strtoupper(ROOX_PLUGIN) . "\n";
                $install_notes .= "Module: $module_installed\n";
                $install_notes .= "Installed on ".date("Y-m-d", time())."\n\n";
                $install_notes .= "Installation files:\n";

                foreach($filesExtracted as $n=>$path)
                {
                    if(basename($path)=='install.php')
                    {
                        continue;
                    }
                    $install_notes .= ($n + 1).". {$path}\n";
                }
                $install_notes .= ($n + 2).". {$module_path}install_note.txt";

                file_put_contents($module_path."install_note.txt", $install_notes);

                if(is_file($installFile))
                {
                    unlink($installFile);
                }
                return ['success'=>true, 'module_path'=>$module_path, 'module_name'=>$module_installed];
            }
            else
            {
                return ['success'=>false];
                //return false;
            }
        }  
        return ['success'=>false];
    }

    static function alert($message, $title = TEXT_INFO, $type='success')
    {
        return "<script>roox.alert('{$message}', '{$title}', '{$type}');</script>";
    } 

}
?>