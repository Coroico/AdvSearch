<?php

switch ($modx->event->name) {
    case 'OnDocFormSave':
    case 'OnTemplateSave':
    case 'OnTempFormSave':
    case 'OnTVFormSave':
    case 'OnSnipFormSave':
    case 'OnPluginFormSave':
    case 'OnMediaSourceFormSave':
    case 'OnChunkFormSave':
    case 'OnSiteRefresh':
        $cacheManager = $modx->getCacheManager();
        $cacheManager->refresh(array(
            'advsearch' => array(),
        ));
        break;
}

return;