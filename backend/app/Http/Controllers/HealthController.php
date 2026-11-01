<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->checkDatabase();
        $checks = [
            'database' => $database,
            'cache' => $this->checkCache($database['status'] === 'up'),
        ];

        $healthy = collect($checks)
            ->every(fn (array $check): bool => $check['status'] === 'up');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'sqlite') {
                $config = $connection->getConfig();
                $host = (string) ($config['host'] ?? '127.0.0.1');
                $port = (int) ($config['port'] ?? 3306);
                $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 1.0);

                if ($socket === false) {
                    throw new \RuntimeException(
                        "Database TCP check failed ({$errorCode}): {$errorMessage}"
                    );
                }

                fclose($socket);
            }

            $connection->getPdo();
            $connection->select('SELECT 1');

            return ['status' => 'up'];
        } catch (Throwable $exception) {
            Log::error('health.database_failed', [
                'actor' => 'healthcheck',
                'component' => 'database',
                'reason' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return ['status' => 'down'];
        }
    }

    private function checkCache(bool $databaseIsUp): array
    {
        $key = 'healthcheck:'.bin2hex(random_bytes(8));

        if (!$databaseIsUp) {
            Log::error('health.cache_failed', [
                'actor' => 'healthcheck',
                'component' => 'cache',
                'reason' => 'database dependency is unavailable',
            ]);

            return ['status' => 'down'];
        }

        try {
            Cache::put($key, 'ok', 10);

            if (Cache::get($key) !== 'ok') {
                throw new \RuntimeException('Cache read-after-write check failed.');
            }

            Cache::forget($key);

            return ['status' => 'up'];
        } catch (Throwable $exception) {
            Log::error('health.cache_failed', [
                'actor' => 'healthcheck',
                'component' => 'cache',
                'reason' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return ['status' => 'down'];
        }
    }
}
