<?php
    //EDO: lets change the menu item
    if(in_array('lingua', ${ROOX_PLUGIN . "_modules"}))
    {
        $sidebarMenu = Roox\Lingua::buildMainMenu();
    }
    else
    {
        $sidebarMenu = build_main_menu();
    }
    //EOM
    echo renderSidebarMenu($sidebarMenu);
?>