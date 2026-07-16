<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $company_id
 * @property int $operator_id
 * @property int|null $percentage
 * @property int $is_custom
 * @property int $created_at
 * @property int $updated_at
 *
 * @property \backend\models\User $operator
 */
class CallCenterDistribution extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%call_center_distribution}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['company_id', 'operator_id'], 'required'],
            [['company_id', 'operator_id'], 'integer'],
            [['is_custom'], 'boolean'],
            [['is_custom'], 'default', 'value' => 0],
            [['percentage'], 'default', 'value' => null],
            [['percentage'], 'integer', 'min' => 1, 'max' => 100],
            [['percentage'], 'required', 'when' => function ($model) {
                return $model->is_custom == 1;
            }, 'whenClient' => "function(attribute, value) {
                return $('#call_center_distribution_is_custom').is(':checked');
            }"],
            [['operator_id'], 'unique', 'targetAttribute' => ['company_id', 'operator_id'],
                'message' => 'This operator is already configured for this company.'],
            [['operator_id'], 'exist', 'targetClass' => \backend\models\User::class,
                'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'company_id'  => 'Company',
            'operator_id' => 'Operator',
            'percentage'  => 'Percentage (%)',
            'is_custom'   => 'Custom Percentage',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getOperator()
    {
        return $this->hasOne(\backend\models\User::class, ['id' => 'operator_id']);
    }

    /**
     * Get all distribution settings for a company
     */
    public static function getForCompany(int $companyId): array
    {
        return static::find()
            ->where(['company_id' => $companyId])
            ->with('operator')
            ->all();
    }

    /**
     * Build slot cycle array for weighted round-robin
     * Returns array of operator_ids representing one full cycle
     */
    public static function buildCycle(int $companyId): array
    {
        $records = static::getForCompany($companyId);

        if (empty($records)) {
            return [];
        }

        $effectivePercentages = static::calculateEffectivePercentages($records);

        if (empty($effectivePercentages)) {
            return [];
        }

        // Normalize to smallest integer slots via GCD
        $gcd = array_reduce(array_values($effectivePercentages), function ($carry, $item) {
            return static::gcd((int)$carry, (int)$item);
        }, array_values($effectivePercentages)[0]);

        $cycle = [];
        foreach ($effectivePercentages as $operatorId => $pct) {
            $slots = (int)round($pct / $gcd);
            for ($i = 0; $i < $slots; $i++) {
                $cycle[] = $operatorId;
            }
        }

        return $cycle;
    }

    /**
     * Calculate effective percentage for each operator
     * Custom operators keep their %, auto operators split the remainder equally
     */
    public static function calculateEffectivePercentages(array $records): array
    {
        $customTotal = 0;
        $autoOperators = [];
        $result = [];

        foreach ($records as $record) {
            if ($record->is_custom && $record->percentage > 0) {
                $customTotal += $record->percentage;
                $result[$record->operator_id] = $record->percentage;
            } else {
                $autoOperators[] = $record->operator_id;
            }
        }

        if ($customTotal > 100) {
            return $result;
        }

        if (!empty($autoOperators)) {
            $remainder = 100 - $customTotal;
            $autoPercentage = $remainder / count($autoOperators);
            foreach ($autoOperators as $operatorId) {
                $result[$operatorId] = $autoPercentage;
            }
        }

        return $result;
    }

    /**
     * Reset distribution cycle position for a company
     */
    public static function resetCycle(int $companyId): void
    {
        \common\models\Companies::updateAll(
            ['distribution_position' => 0],
            ['id' => $companyId]
        );
    }

    /**
     * Get next operator_id for a new candidate using weighted round-robin
     * Atomically increments position to avoid race conditions
     */
    public static function getNextOperatorId(int $companyId): ?int
    {
        $cycle = static::buildCycle($companyId);

        if (empty($cycle)) {
            return null;
        }

        $cycleLength = count($cycle);

        // Atomic increment to handle concurrent requests
        \Yii::$app->db->createCommand(
            'UPDATE {{%companies}} SET distribution_position = distribution_position + 1 WHERE id = :id',
            [':id' => $companyId]
        )->execute();

        $company = Companies::findOne($companyId);
        $position = ($company->distribution_position - 1) % $cycleLength;

        return $cycle[$position];
    }

    /**
     * Euclidean GCD
     */
    private static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return $a ?: 1;
    }
}