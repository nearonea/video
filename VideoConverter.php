<?php

namespace common\modules\video;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Filters\Video\WatermarkFilter;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * Компонент для работы с видео.
 * @package common\modules\video
 */
class VideoConverter extends Component
{
    /**
     * @var string папка с видео.
     */
    public string $videosDir;

    /**
     * @var VideoMetadataReader ридер метаданных.
     */
    public VideoMetadataReader $metadataReader;

    /**
     * Создает превью для видео.
     * @param string $fileName
     * @param $previewName
     * @return string
     */
    public function thumbnail(string $fileName): string
    {
        $lib = FFMpeg::create();

        $video = $lib->open($fileName);

        $newFileName = $this->videosDir . "/" . md5($fileName . mt_rand() . mt_rand()) . ".png";

        $image = $video->frame(new TimeCode(0, 0, 2, 1));

        $image->save($newFileName, true);

        return $newFileName;
    }

    /**
     * Конвертирует видео в MP4 и вернет путь к нему. Может наложить вотермарк или порезать качество.
     * @param string $fileName название файла.
     * @param string $quality качество.
     * @param string $watermark вотермарк.
     * @param bool $repeatWatermark
     * @return string
     */
    public function convert(
        string $fileName,
        string $quality = null,
        string $watermark = null,
        bool $repeatWatermark = false
    ): string {
        if ($watermark !== null && !file_exists($watermark)) {
            throw new InvalidArgumentException("The watermark \"{$watermark}\" is not a file or does not exist");
        }

        $lib = FFMpeg::create();

        $video = $lib->open($fileName);

        $newFileName = $this->videosDir . "/" . md5($fileName . mt_rand() . mt_rand()) . ".mp4";

        // порядок важен: сперва мы меняем разрешение
        if ($quality != null) {
            $videostream = $lib->getFFProbe()->streams($fileName)->videos()->first();
            $tags = $videostream->get('tags');
            //this hack for vertical iphone video, thx Tim Cook
            if (isset($tags['rotate']) && $tags['rotate'] != '0' && $this->metadataReader->isVertical($fileName) == false) {
                $dimension = $this->makeDimension($quality, true);
            } else {
                $dimension = $this->makeDimension($quality, $this->metadataReader->isVertical($fileName));
            }

            $video->addFilter(
                new ResizeFilter($dimension)
            );
        }

        // а теперь ставим вотермарк. Такой порядок быстрее всего конвертирует видео.
        if ($watermark !== null) {
            if ($repeatWatermark) {
                $this->addRepeatedWatermark(
                    $video,
                    $watermark,
                    isset($dimension) ? [$dimension->getWidth(), $dimension->getHeight()] : null
                );
            } else {
                $video->addFilter(
                    new WatermarkFilter($watermark, ["position" => "relative", "bottom" => 50, "right" => 50])
                );
            }
        }

        $format = new X264();
        $format
            ->setAudioCodec("libfdk_aac")
            ->setVideoCodec("libx264");

        $video->save($format, $newFileName);

        return $newFileName;
    }

    /**
     * Возвращает разрешение видео согласно "народному" качеству.
     * @param string $quality
     * @param bool $isVertical
     * @return Dimension
     */
    private function makeDimension(string $quality, bool $isVertical = false): Dimension
    {
        $qualities = $this->metadataReader->getQualities();

        if (!isset($qualities[$quality])) {
            throw new InvalidArgumentException("Unknown quality: {$quality}");
        }

        [$width, $height] = $qualities[$quality];

        if ($isVertical) {
            [$width, $height] = [$height, $width];
        }

        return new Dimension(
            $width,
            $height
        );
    }

    /**
     * Добавляет
     * @param Video $video
     * @param string $watermark
     * @param array|null $dimensions
     */
    private function addRepeatedWatermark(Video $video, string $watermark, array $dimensions = null): void
    {
        if ($dimensions === null) {
            [$vWidth, $vHeight] = $this->metadataReader->getDimensions($video->getPathfile());
        } else {
            [$vWidth, $vHeight] = $dimensions;
        }

        [$wWidth, $wHeight] = getimagesize($watermark);

        $x = ($vWidth - $wWidth) / 2;
        $y = ($vHeight - $wHeight) / 2;

        $video->addFilter(
            new WatermarkFilter($watermark, ["position" => "absolute", "x" => $x, "y" => $y])
        );
    }

    /**
     * Возвращает длительность видео в секундах.
     * @param string $file название файла.
     * @return int
     */
    public function duration(string $file): int
    {
        $lib = FFProbe::create();

        return $lib->streams($file)->videos()->first()->get("duration");
    }
}
