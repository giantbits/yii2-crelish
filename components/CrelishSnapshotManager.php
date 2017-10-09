<?php
namespace giantbits\crelish\components;

class CrelishSnapshotManager {
    private static $snapshots = null;
    private static $currentSnapshot = null;


    public static function getSnapshots() {
        if (CrelishSnapshotManager::$snapshots == null) {
            CrelishSnapshotManager::loadSnapshots();
        }
        return CrelishSnapshotManager::$snapshots;
    }

    public static function createSnapshotEntry($key) {
        if (CrelishSnapshotManager::$snapshots ==  null) {
            CrelishSnapshotManager::loadSnapshots();
        }
        CrelishSnapshotManager::$snapshots[$key] = ["key"=>$key];
        CrelishSnapshotManager::saveSnapshots();
    }

    public static function setCurrentSnapshot($key) {
        if (CrelishSnapshotManager::$snapshots == null) {
            CrelishSnapshotManager::loadSnapshots();
        }
        if (isset(CrelishSnapshotManager::$snapshots[$key])) {
            CrelishSnapshotManager::$currentSnapshot = CrelishSnapshotManager::$snapshots[$key];
        }
    }

    public function getCurrentSnapshot() {
        return CrelishSnapshotManager::$currentSnapshot;
    }

    private static function loadSnapshots() {
        if (isset(\Yii::$app->params['crelish']['snapshotFile'])) {
            $file = \Yii::getAlias('@app/workspace') . '/' . \Yii::$app->params['crelish']['snapshotFile'];
            if (file_exists($file)) {
                CrelishSnapshotManager::$snapshots = json_decode(file_get_contents($file),true);
            }
        }
        if (is_null(CrelishSnapshotManager::$snapshots)) {
            CrelishSnapshotManager::$snapshots = [];
        }
    }

    private static function saveSnapshots() {
        if (isset(\Yii::$app->params['crelish']['snapshotFile'])) {
            $file = \Yii::getAlias('@app/workspace') . '/' . \Yii::$app->params['crelish']['snapshotFile'];
            file_put_contents($file, json_encode(CrelishSnapshotManager::$snapshots));
        }
    }


    public static function setSnapshotCacheKey(&$cacheKey) {
        if (CrelishSnapshotManager::$currentSnapshot !== null) {
            $cacheKey .= "_" . CrelishSnapshotManager::$currentSnapshot['key'];
        }
    }

    public static function setWorkspaceDir(&$workspaceDir) {
        if (CrelishSnapshotManager::$currentSnapshot !== null) {
            $workspaceDir .= '/snapshots/'.CrelishSnapshotManager::$currentSnapshot['key'] .'/workspace';
        }
    }

    public static function updateViewAndTheme($controller) {
        if (CrelishSnapshotManager::$currentSnapshot !== null) {
            $controller->view->theme = new \yii\base\Theme([
                'pathMap' => [
                    '@app/views' => '@app/themes/snapshots/' . CrelishSnapshotManager::$currentSnapshot['key'] .'/'. \giantbits\crelish\Module::getInstance()->theme
                ],
                'basePath' => '@app/themes/snapshots/' . CrelishSnapshotManager::$currentSnapshot['key'] .'/' . \giantbits\crelish\Module::getInstance()->theme,
                'baseUrl' => '@web/themes/snapshots/' . CrelishSnapshotManager::$currentSnapshot['key'] .'/' . \giantbits\crelish\Module::getInstance()->theme,
            ]);

            // Force theming.
            $controller->setViewPath('@app/themes/snapshots/' . CrelishSnapshotManager::$currentSnapshot['key'] .'/' . \giantbits\crelish\Module::getInstance()->theme . '/' . $controller->id);
        }
    }
}