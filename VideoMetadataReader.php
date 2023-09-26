<?php

namespace common\modules\video;

use FFMpeg\FFProbe;
use yii\base\Component;

/**
 * Работает с метаданными видео.
 * @package common\modules\video
 */
class VideoMetadataReader extends Component
{
    /**
     * Узнает разрешение видео и возвращает его параметры в массиве [ширина, высота].
     * @param string $fileName
     * @return array
     */
    public function getDimensions(string $fileName): array
    {
        $ffprobe = FFProbe::create(
            [
                "ffprobe.binaries" => "/usr/bin/ffprobe"
            ]
        );

        $dimensions = $ffprobe
            ->streams($fileName)
            ->videos()
            ->first()
            ->getDimensions();

        return [$dimensions->getWidth(), $dimensions->getHeight()];
    }

    /**
     * Возвращает true, если видео вертикальное.
     * @param string $fileName
     * @return bool
     */
    public function isVertical(string $fileName): bool
    {
        [$width, $height] = $this->getDimensions($fileName);

        return $width < $height;
    }

    /**
     * Возвращает "ближайшее" и высокое качество видео.
     * @param int $height высота.
     * @return string|null
     */
    public function getQuality(int $height): ?string
    {
        $qualities = $this->getQualities();

        foreach ($qualities as $quality => $dimensions) {
            if ($dimensions[1] >= $height) {
                return $quality;
            }
        }

        return array_key_last($qualities);
    }

    /**
     * Возвращает *отсортированный* по возрастанию массив с информацией о качествах видео в формате
     * [качество => [ширина, высота]].
     * @return array
     */
    public function getQualities(): array
    {
        return [
            '240p' => [352, 240],
            '360p' => [480, 360],
            '480p' => [858, 480],
            '720p' => [1280, 720],
            '1080p' => [1920, 1080],
            '2160p' => [3860, 2160]
        ];
    }
}
