<?php

namespace modules\demos;

use Craft;

use craft\awss3\Volume as AwsVolume;
use craft\volumes\Local as LocalVolume;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use yii\web\Response;
use craft\web\View;
use yii\base\Event;

class Module extends \yii\base\Module
{
    public function init(): void
    {
        Craft::setAlias('@modules/demos', __DIR__);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\demos\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\demos\\controllers';
        }

        parent::init();

        if (!App::env('S3_BUCKET')) {
            $this->_useLocalVolumes();
        }

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['modules'] = __DIR__ . '/templates';
            }
        );

        Event::on(
            Response::class,
            Response::EVENT_BEFORE_SEND,
            static function ($event) {
                /* @var Response */
                $response = $event->sender;
                $request = Craft::$app->getRequest();

                if (preg_match('/\.gif$/', $request->getFullPath())) {
                    $response->clear();
                }
            }
        );
    }

    private function _useLocalVolumes()
    {
        Craft::$container->set(AwsVolume::class, function ($container, $params, $config) {
            if (empty($config['id'])) {
                return new AwsVolume($config);
            }

            return new LocalVolume([
                'id' => $config['id'],
                'uid' => $config['uid'],
                'name' => $config['name'],
                'handle' => $config['handle'],
                'hasUrls' => $config['hasUrls'],
                'url' => "@web/{$config['subfolder']}",
                'path' => "@webroot/{$config['subfolder']}",
                'sortOrder' => $config['sortOrder'],
                'dateCreated' => $config['dateCreated'],
                'dateUpdated' => $config['dateUpdated'],
                'fieldLayoutId' => $config['fieldLayoutId'],
            ]);
        });
    }
}
