<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'created_by') && empty($model->created_by)) {
                    $model->created_by = Auth::id();
                }
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by') && empty($model->updated_by)) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        if (method_exists(static::class, 'restored')) {
            static::restoring(function ($model) {
                if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                    $model->updated_by = Auth::id();
                }
            });
        }

        static::deleting(function ($model) {
            if (Auth::check() && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'deleted_by')) {
                    $model->deleted_by = Auth::id();
                    $model->save();
                }
            }
        });
    }
}
