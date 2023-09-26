<?php

namespace common\modules\video;

use common\modules\video\jobs\ConvertJob;
use common\modules\video\jobs\PreviewJob;
use common\modules\video\models\Video;
use common\modules\video\models\VideoQuality;
use Exception;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\queue\Queue;

/**
 * Сервис для работы с видео. Создаст необходимые VideoQuality и поместит обработку в очередь.
 * @package common\modules\video
 */
class VideoService extends Component
{
    /**
     * @var VideoMetadataReader ридер метаданных.
     */
    public VideoMetadataReader $metadataReader;

    /**
     * @var array доступные качества видео. Всё видео будет уменьшаться до этого качества. Порядок важен.
     */
    public array $allowQualities = ["1080p", "720p"];

    /**
     * @param string $originalFileName
     * @return Video
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function process(string $originalFileName): Video
    {
        [, $height] = $this->metadataReader->getDimensions($originalFileName);

        /** @var VideoMetadataReader $metadataReader */
        $metadataReader = Yii::$app->get("video-metadata-reader");

        $videoFileQuality = $metadataReader->getQuality($height);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $video      = new Video();
            $video->src = $originalFileName;
            $video->save();

            $videoQualities = [];

            foreach ([true, false] as $isDemo) {
                foreach ($this->allowQualities as $allowQuality) {
                    $videoQuality = new VideoQuality();

                    $videoQuality->video_id = $video->id;
                    $videoQuality->quality  = substr($allowQuality, 0, -1);
                    $videoQuality->src      = null;
                    $videoQuality->status   = VideoQuality::STATUS_NEW;
                    $videoQuality->is_demo  = $isDemo;

                    $videoQuality->save();

                    $videoQualities[] = $videoQuality;

                    if ($allowQuality == $videoFileQuality) {
                        break;
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $throwable) {
            $transaction->rollBack();

            throw $throwable;
        }

        /** @var Queue $queue */
        $queue = Yii::$app->get("queue");

        $queue->push(
            new PreviewJob(
                [
                    "video" => $video
                ]
            )
        );

        foreach ($videoQualities as $videoQuality) {
            $queue->push(
                new ConvertJob(
                    [
                        "videoQuality" => $videoQuality
                    ]
                )
            );
        }

        return $video;
    }
}
