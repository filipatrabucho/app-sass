<?php

namespace App\Core\Audit\Concerns;

use App\Core\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Regista created/updated/deleted em audit_logs (BD do tenant).
 *
 * Os atributos em $hidden e os de $auditMasked (ex.: iban) nunca são guardados em claro.
 * Um model pode definir $auditExclude para ignorar colunas (ex.: last_login_at).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAudit('created', null, $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->auditValues($model->getChanges());
            if ($changes === []) {
                return;
            }
            $old = $model->auditValues(array_intersect_key($model->getOriginal(), $changes));
            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(fn (Model $model) => $model->writeAudit('deleted', $model->auditValues($model->getAttributes()), null));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => $model->writeAudit('restored', null, null));
        }
    }

    protected function auditValues(array $values): array
    {
        $exclude = [...($this->auditExclude ?? []), 'created_at', 'updated_at', 'remember_token'];
        $values = array_diff_key($values, array_flip($exclude));

        foreach ([...$this->getHidden(), ...($this->auditMasked ?? [])] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '***';
            }
        }

        return $values;
    }

    protected function writeAudit(string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => auth()->guard('web')->id(),
            'action' => $action,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => (string) $this->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
