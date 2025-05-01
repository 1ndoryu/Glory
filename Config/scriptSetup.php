<?php

use Glory\Class\ScriptManager;
use Glory\Class\StyleManager;
use Glory\Class\GloryLogger; 


ScriptManager::defineFolder('Glory/Assets/js');
#StyleManager::defineFolder('Glory/Assets/css'); 
StyleManager::define('EmailSignupModal', 'Glory/Assets/css/EmailSignupModal.css');

GloryLogger::init();