<?php

namespace common\modules\video\jobs;

use common\modules\video\models\Video;
use common\modules\video\models\VideoQuality;

trait OriginalRemoveTrait
{
    /**
     * Удаляет файл оригинала видео, если это необходимо.
     * Проверит, есть ли еще VideoQuality в обработке. Если все VideoQuality готовы (со статусом === STATUS_ALREADY), то
     * удалит оригинал. В противном случае - оставит всё как есть.
     * @param Video $video
     */
    private function removeOriginalIfNeeded(Video $video): void
    {
        if ($video->preview_src !== null) {
            return;
        }

        foreach ($video->qualities as $quality) {
            if ($quality->status != VideoQuality::STATUS_ALREADY) {
                return;
            }
        }

        if (file_exists($video->src)) {
            unlink($video->src);
        }
    }
}
