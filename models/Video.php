<?php

namespace common\modules\video\models;

use JsonSerializable;
use Throwable;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\helpers\Url;

/**
 * Модель видео.
 * @package common\modules\video\models
 * @property int $id ID записи.
 * @property string $src путь к оригиналу файла.
 * @property string $preview_src путь к превью.
 * @property int $created_at дата создания.
 * @property int $updated_at дата изменения.
 * @property-read VideoQuality[] $qualities
 */
class Video extends ActiveRecord implements JsonSerializable
{
    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return "videos";
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            [
                "class" => TimestampBehavior::class,
                "value" => date("Y-m-d H:i:s")
            ]
        ];
    }

    /**
     * Возвращает запрос на получение всех версий этого видео.
     * @return ActiveQuery
     */
    public function getQualities(): ActiveQuery
    {
        return $this->hasMany(VideoQuality::class, ["video_id" => "id"]);
    }

    /**
     * Возвращает ссылку на превью.
     * @return string
     */
    public function getPreviewUri(): string
    {
        return "/files/" . basename($this->preview_src);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function afterDelete()
    {
        @unlink($this->src);

        foreach ($this->qualities as $quality) {
            $quality->delete();
        }

        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "preview_src" => Url::base(true) . $this->getPreviewUri()
        ];
    }
}
