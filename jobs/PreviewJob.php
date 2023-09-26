<?php

namespace common\modules\video\jobs;

use common\modules\video\models\Video;
use common\modules\video\VideoConverter;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\queue\JobInterface;

/**
 * Вытаскивает и устанавливает превью к видео.
 * @package common\modules\video\jobs
 */
class PreviewJob extends BaseObject implements JobInterface
{
    use OriginalRemoveTrait;

    /**
     * @var Video видео, к которому будем прикреплять превью.
     */
    public Video $video;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function execute($queue)
    {
        $video = $this->video;

        /** @var VideoConverter $converter */
        $converter = Yii::$app->get("video-converter");

        $video->preview_src = $converter->thumbnail($video->src);
        $video->save();

        $this->removeOriginalIfNeeded($video);
    }
}
