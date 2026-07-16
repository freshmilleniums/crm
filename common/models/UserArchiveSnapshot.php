<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $snapshot_data
 * @property int $archived_by
 * @property int $archived_at
 *
 * @property User $user
 * @property User $archivedByUser
 */
class UserArchiveSnapshot extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_archive_snapshots';
    }

    public function rules()
    {
        return [
            [['user_id', 'snapshot_data', 'archived_by', 'archived_at'], 'required'],
            [['user_id', 'archived_by', 'archived_at'], 'integer'],
            [['snapshot_data'], 'string'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['archived_by'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['archived_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'user_id'       => 'User',
            'snapshot_data' => 'Snapshot Data',
            'archived_by'   => 'Archived By',
            'archived_at'   => 'Archived At',
        ];
    }

    public function getSnapshotData(): array
    {
        return $this->snapshot_data ? json_decode($this->snapshot_data, true) : [];
    }

    public function setSnapshotData(array $data): void
    {
        $this->snapshot_data = json_encode($data);
    }

    public function getProjectIds(): array
    {
        return $this->getSnapshotData()['projects'] ?? [];
    }

    public function getInvestorIds(): array
    {
        return $this->getSnapshotData()['investors'] ?? [];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getArchivedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'archived_by']);
    }
}