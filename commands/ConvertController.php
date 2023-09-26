<?php

namespace common\modules\video\commands;

use common\modules\video\jobs\OldConvertJob;
use common\modules\video\models\Video;
use common\modules\video\models\VideoQuality;
use common\modules\video\VideoConverter;
use common\modules\video\VideoService;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\queue\Queue;

/**
 * Контроллер для конвертирования видео.
 * @package common\modules\video
 */
class ConvertController extends Controller
{
    /**
     * Конвертирование файла.
     * @param string $video путь к файлу.
     * @param int $quality качество.
     * @param string $watermark вотермарк.
     * @return int
     * @throws InvalidConfigException
     */
    public function actionIndex($video, $quality = null, $watermark = null)
    {
        /** @var VideoConverter $converter */
        $converter = Yii::$app->get("video-converter");

        $path = $converter->convert($video, $quality, $watermark, true);

        $this->stdout($path . PHP_EOL);

        return 0;
    }

    public function actionDuration()
    {
        /** @var VideoConverter $converter */
        $converter = Yii::$app->get("video-converter");

        $videoQualities = VideoQuality::find()->where(["IS NOT", "src", null])->all();

        /** @var VideoQuality $videoQuality */
        foreach ($videoQualities as $videoQuality) {
            if (!file_exists($videoQuality->src)) {
                continue;
            }
            $videoQuality->duration = $converter->duration($videoQuality->src);
            $videoQuality->save();
        }

        return 0;
    }

    public function actionThumbnail($video)
    {
        /** @var VideoConverter $converter */
        $converter = Yii::$app->get("video-converter");

        $path = $converter->thumbnail($video);

        $this->stdout($path . PHP_EOL);

        return 0;
    }

    public function actionList()
    {
        /** @var Video[] $videos */
        $videos = Video::find()->all();

        foreach ($videos as $video) {
            $this->stdout("#{$video->id} - {$video->created_at}\n");
        }

        return 0;
    }

    /**
     * @param $video
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function actionToQueue($video)
    {
        /** @var VideoService $service */
        $service = Yii::$app->get("video-service");

        $service->process($video);
    }
}
