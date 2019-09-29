<?php

namespace tourhunter\devUpdater;

use yii\web\Controller;
use yii\web\Response;

/**
 * Class DevUpdaterController
 *
 * @package tourhunter\devUpdater
 */
class DevUpdaterController extends Controller
{

    /**
     * @var null|DevUpdaterComponent
     */
    public $devUpdater = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->devUpdater = \Yii::$app->get(DevUpdaterComponent::getRegisteredComponentId());
    }

    /**
     * Main action with interactive page
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = false;
        $this->viewPath = __DIR__ . '/views';

        $this->devUpdater->checkAllWarnings();

        return $this->render('index', [
            'devUpdater' => $this->devUpdater,
        ]);
    }

    /**
     * Starts update process
     *
     * @return null|string|Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun()
    {
        $response = null;
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $response = $this->devUpdater->runAllUpdates() ? 'done' : 'error';
        } else {
            $response = $this->redirect($this->devUpdater->controllerId . '/index');
        }

        return $response;
    }

    /**
     * Discard update process
     *
     * @return Response
     */
    public function actionDiscard()
    {
        $this->devUpdater->getInfoStorage()->setLastUpdateInfo(DevUpdaterComponent::INFO_LAST_UPDATE_TIME, time());
        $this->devUpdater->getInfoStorage()->saveLastUpdateInfo();

        return $this->redirect([$this->devUpdater->controllerId . '/index']);
    }
}