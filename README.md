# Roox
Roox is a Rukovoditel plugin that gives additional features to your application for some specific purposes. 

Roox modules can be installed separately via module installer, with or without interdependency.

This repository only contains module core for the base of other modules.

Features:
* Module installer, to make installation of other Roox's modules easier;
* Dictionary, to record definitions/constants used in plugin, like language files in Rukovoditel. It can be translated to other languages if you install module [Lingua](https://github.com/eddydeniro/roox-lingua).  


How to install:
* Copy all files to a folder named "roox" inside folder plugins of your Rukovoditel installation.
* Don't forget to add 'roox' in AVAILABLE_PLUGINS section of config/file server.php.
* Open and login to your Rukovoditel. The module will automatically install some required tables (with prefix 'roox') in your Rukovoditel database.
* You will immediately see Roox in the sidebar menu. 
