<?php

namespace common\modules\video;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\console\Application as CliApplication;
use yii\queue\cli\Queue;

/**
 * Модуль работы с видео.
 * @package common\modules\video
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @var string папка, где будут храниться файлы.
     */
    public ?string $videosDir = null;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->id = "video";

        if (Yii::$app instanceof CliApplication) {
            $this->controllerNamespace = "common\\modules\\video\\commands";
        } else {
            $this->controllerNamespace = "common\\modules\\video\\controllers";
        }

        if ($this->videosDir === null) {
            $this->videosDir = __DIR__ . "/_files";
        }

        if (!file_exists($this->videosDir)) {
            mkdir($this->videosDir, 0777, true);
        }

        $this->videosDir = realpath($this->videosDir);

        parent::init();
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function bootstrap($app)
    {
        $app->set(
            "video-metadata-reader",
            [
                "class" => VideoMetadataReader::class
            ]
        );

        $app->set(
            "video-converter",
            [
                "class" => VideoConverter::class,
                "videosDir" => $this->videosDir,
                "metadataReader" => $app->get("video-metadata-reader")
            ]
        );

        $app->set(
            "video-service",
            [
                "class" => VideoService::class,
                "metadataReader" => $app->get("video-metadata-reader")
            ]
        );
    }
}
