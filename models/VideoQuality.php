<?php

namespace common\modules\video\models;

use JsonSerializable;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Модель версии видео.
 * @property int $id ID записи.
 * @property int $video_id ID видео.
 * @property int $quality качество.
 * @property int $status статус.
 * @property string $src путь к файлу.
 * @property int $duration длительность видео в секундах.
 * @property boolean $is_demo является ли демо-версией (т.е. с вотермаркой по центру).
 * @property-read Video $video видео.
 * @package common\modules\video\models
 */
class VideoQuality extends ActiveRecord implements JsonSerializable
{
    /**
     * Статусы видео: новый, в обработке, ошибка и готов к использованию.
     */
    const STATUS_NEW = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_ERROR = 2;
    const STATUS_ALREADY = 3;

    const IS_DEMO = 1;
    const IS_FINAL = 0;

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return "video_qualities";
    }

    /**
     * Возвращает родительское видео.
     * @return ActiveQuery
     */
    public function getVideo(): ActiveQuery
    {
        return $this->hasOne(Video::class, ["id" => "video_id"]);
    }

    /**
     * Возвращает URL к видео.
     * @return string
     */
    public function getUri(): string
    {
        return "/files/" . basename($this->src);
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        @unlink($this->src);

        parent::afterDelete();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "quality" => $this->quality,
            "src" => Url::base(true) . $this->getUri(),
            "is_demo" => $this->is_demo
        ];
    }
}
