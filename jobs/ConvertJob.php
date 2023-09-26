<?php

namespace common\modules\video\jobs;

use common\modules\video\models\VideoQuality;
use common\modules\video\VideoConverter;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Задача для конвертирования видео.
 * @package common\modules\video\jobs
 */
class ConvertJob extends BaseObject implements JobInterface
{
    use OriginalRemoveTrait;

    /**
     * @var VideoQuality модель, которую будем использовать для изменения статусов.
     */
    public VideoQuality $videoQuality;

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function execute($queue)
    {
        $originalVideo = $this->videoQuality->video;

        $videoQuality = $this->videoQuality;

        try {
            $videoQuality->status = VideoQuality::STATUS_IN_PROCESS;
            $videoQuality->save();

            /** @var VideoConverter $converter */
            $converter = Yii::$app->get("video-converter");

            $watermark = __DIR__ . "/../watermark.png";
            $repeatWatermark = false;

            if ($videoQuality->is_demo) {
                $watermark = __DIR__ . "/../watermark-repeat.png";
                $repeatWatermark = true;
            }

            $file = $converter->convert(
                $originalVideo->src,
                $videoQuality->quality . "p",
                $watermark,
                $repeatWatermark
            );

            $videoQuality->src = $file;
            $videoQuality->duration = $converter->duration($file);
            $videoQuality->status = VideoQuality::STATUS_ALREADY;
            $videoQuality->save();
        } catch (Throwable $throwable) {
            $videoQuality->status = VideoQuality::STATUS_ERROR;
            $videoQuality->save();

            throw $throwable;
        }

        $this->removeOriginalIfNeeded($originalVideo);
    }
}
