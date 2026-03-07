<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;

abstract class Controller
{
    /**
     * 外部キー制約違反として扱うべき QueryException かを判定する。
     */
    protected function isForeignKeyConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        return $sqlState === '23503'
            || $driverCode === 1451
            || ($sqlState === '23000' && str_contains($message, 'foreign key'));
    }
}
