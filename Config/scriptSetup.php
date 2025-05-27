<?php

use Glory\Class\ScriptManager;
use Glory\Class\StyleManager;
use Glory\Class\GloryLogger; 

#Esto no esta preparado para ejecutarse en el wp-admin, aún no se como hacer que scriptmanager funcione en wp-admin
ScriptManager::defineFolder('/Glory/Assets/js');
StyleManager::defineFolder('/Glory/assets/css'); 
#Glory\Assets\css\adminPanel.css

GloryLogger::init();