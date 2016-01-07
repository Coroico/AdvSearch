<?php

/**
 * AdvSearch resolver script - runs on install.
 *
 * Copyright 2012 Coroico <coroico@wangba.fr>
 * @author Coroico <coroico@wangba.fr>
 * @author goldsky <goldsky@virtudraft.com>
 * 07/1/2016
 *
 * AdvSearch is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * AdvSearch is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AdvSearch; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package advsearch
 */
/**
 * Description: Resolver script for AdvSearch package
 * @package advsearch
 * @subpackage build
 */
/* The $modx object is not available here. In its place we
 * use $object->xpdo
 */

$modx = & $object->xpdo;

$success = true;

$modx->log(xPDO::LOG_LEVEL_INFO, 'Running PHP Resolver.');
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $advsearch = $modx->getService('advsearch', 'AdvSearch', MODX_CORE_PATH . 'components/advsearch/model/advsearch/');
        $pluginObj = $modx->getObject('modPlugin', array('name' => 'AdvSearchSolr'));
        if ($advsearch && $pluginObj) {
            $reqdFile = $advsearch->config['libraryPath'] . 'solarium/library/Solarium/Autoloader.php';
            if (!file_exists($reqdFile)) {
                $pluginObj->set('disabled', 1);
                $pluginObj->save();
            }
        }

        $success = true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Uninstalling . . .');
        $success = true;
        break;
}
$modx->log(xPDO::LOG_LEVEL_INFO, 'Script resolver actions completed');
return $success;
