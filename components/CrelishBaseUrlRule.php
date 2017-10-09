<?php
/**
 *
 */

namespace giantbits\crelish\components;

use Underscore\Types\Arrays;
//use Underscore\Types\Strings;
use yii\web\UrlRuleInterface;

/**
 *
 */
class CrelishBaseUrlRule implements UrlRuleInterface {

    var $snapshots = [];
    /**
     * [init description]
     * @return [type] [description]
     */
    public function init() {
        parent::init();
    }

    public function createUrl($manager, $route, $params) {

        $url = '';

        if ($route != 'crelish/frontend/run') {
            return FALSE;
        }

        if (array_key_exists('language', $params) && !empty($params['languages'])) {
            $url .= $params['languages'];
        }

        if (array_key_exists('pathRequested', $params) && !empty($params['pathRequested'])) {
            if ($url != '') {
                $url .= '/';
            }

            $url .= $params['pathRequested'];
        }

        $paramsClean = Arrays::remove($params, 'language');
        $paramsClean = Arrays::remove($paramsClean, 'pathRequested');

        $paramsExposed = '?';
        foreach ($paramsClean as $key => $value) {
            $paramsExposed .= $key . '=' . $value . '&';
        }
        $paramsExposed = rtrim($paramsExposed, '&');

        if (strpos($params['pathRequested'], ".html") === FALSE) {
            return $params['pathRequested'] . '.html' . $paramsExposed;
        }
        else {
            return $params['pathRequested'] . $paramsExposed;
        }
    }

    public function parseRequest($manager, $request) {
        $pathInfo = $request->getPathInfo();

        $langFreePath = $pathInfo;
        $langCode = '';

        if (isset(\Yii::$app->params['crelish']['langprefix']) && \Yii::$app->params['crelish']['langprefix']) {
            $pathInfoParts = explode("/", $pathInfo, 2);
            if (strlen($pathInfoParts[0]) == 2) {
                $langCode = $pathInfoParts[0];
                $langFreePath = '';
                if (count($pathInfoParts) > 1) {
                    $langFreePath = $pathInfoParts[1];
                }
            }
        }
        if (empty($langFreePath )) {
            header('Location: '.CrelishBaseUrlRule::urlForSlug(\Yii::$app->params['crelish']['entryPoint']['slug'],$langCode).(strlen($_SERVER['QUERY_STRING']) > 0 ? "?".$_SERVER['QUERY_STRING'] : ''));
            die();
        }

        if (isset(\Yii::$app->params['crelish']['snapshotFile']) && \Yii::$app->user->isGuest === false) {
            foreach (CrelishSnapshotManager::getSnapshots() as $key => $val) {
                if (strtolower(substr($langFreePath,0,strlen($key)+1)) == strtolower($key) . '/') {
                    $langFreePath = substr($langFreePath,strlen($key)+1);
                    CrelishSnapshotManager::setCurrentSnapshot($key);
                    break;
                } else if (strtolower($langFreePath) == strtolower($key)) {
                    CrelishSnapshotManager::setCurrentSnapshot($key);
                    header('Location: '.CrelishBaseUrlRule::urlForSlug(\Yii::$app->params['crelish']['entryPoint']['slug'],$langCode).(strlen($_SERVER['QUERY_STRING']) > 0 ? "?".$_SERVER['QUERY_STRING'] : ''));
                    die();
                }
            }
        }

        if($langFreePath  == 'crelish/' || $langFreePath  == 'crelish.html' || $langFreePath  == 'crelish') {
            header('Location: /crelish/dashboard/index.html');
            die();
        }

        if (strpos($langFreePath, '.html') === FALSE) {
            if (substr($langFreePath, -1) !== "/") {
                $currentSnapshot = CrelishSnapshotManager::getCurrentSnapshot();
                if ($currentSnapshot != null) {
                    header('Location: /' . $currentSnapshot['key'] . '/' . $langFreePath . '.html'.(strlen($_SERVER['QUERY_STRING']) > 0 ? "?".$_SERVER['QUERY_STRING'] : ''));
                } else {
                    header('Location: /' . $langFreePath . '.html'.(strlen($_SERVER['QUERY_STRING']) > 0 ? "?".$_SERVER['QUERY_STRING'] : ''));
                }
                die();
            }
        } else {
            $pathInfo = str_replace('.html', '', $pathInfo);
        }
        if (strpos($langFreePath, '/') > 0) {
            $segments = explode('/', $langFreePath);
            $langFreePath = array_shift($segments);
            $additional = $segments;
        }
        else {
            $additional = [];
        }
        $langFreePath = str_replace('.html','',$langFreePath);

        $params = array_merge($request->queryParams, [
            'pathRequested' => $langFreePath,
            'language' => $langCode,
            $additional
        ]);

        if (!empty($langCode)) {
            \Yii::$app->language = $langCode;
        }

        return ['crelish/frontend/run', $params];
    }

    public static function urlForSlug($slug,$langCode=null) {
        $url = '/' . $slug . '.html';
        if (isset(\Yii::$app->params['crelish']['snapshotFile'])) {
            $snapshot = CrelishSnapshotManager::getCurrentSnapshot();
            if ($snapshot != null) {
                $url = '/' . $snapshot['key'] . $url;
            }
        }

        if (isset(\Yii::$app->params['crelish']['langprefix']) && \Yii::$app->params['crelish']['langprefix']) {
            if (empty($langCode)) {
                $langCode = \Yii::$app->language;
                if (preg_match('/([a-z]{2})-[A-Z]{2}/',$langCode,$sub)) {
                    $langCode = $sub[1];
                }
            }
            $url = '/' . $langCode . $url;
        }
        return $url;
    }
}
