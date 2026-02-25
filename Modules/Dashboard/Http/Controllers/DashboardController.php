<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
	public function getUptime()
	{
		$uptime = 'Unknown';
		if (PHP_OS_FAMILY === 'Windows') {
			$res = shell_exec(
				'powershell -NoProfile -Command "(Get-Date) - (Get-CimInstance Win32_OperatingSystem).LastBootUpTime | Select-Object -ExpandProperty TotalDays"'
			);
			$uptime = $res ? floor(trim($res)) . ' ' . __('days') : $uptime;
		} else {
			$uptime = str_replace('up ', '', trim(shell_exec('uptime -p')));
		}

		return response()->json([
			'success' => true,
			'data' => ['uptime' => $uptime]
		]);
	}

	public function getCpu()
	{
		$usage = 0;
		if (PHP_OS_FAMILY === 'Windows') {
			$cpu = shell_exec(
				'powershell -NoProfile -Command "Get-CimInstance Win32_Processor | Select-Object -ExpandProperty LoadPercentage"'
			);
			$usage = $cpu ? (float) trim($cpu) : 0;
		} elseif (function_exists('sys_getloadavg')) {
			$load = sys_getloadavg();
			$usage = round($load[0] * 10, 2);
		}

		return response()->json([
			'success' => true,
			'data' => ['cpu' => $usage]
		]);
	}

	public function getRam()
	{
		$data = ['used' => 0, 'total' => 0, 'percentage' => 0];
		if (PHP_OS_FAMILY === 'Windows') {
			$osInfo = shell_exec(
				'powershell -NoProfile -Command "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory | ConvertTo-Json"'
			);
			$raw = json_decode($osInfo);
			if ($raw) {
				$total = round($raw->TotalVisibleMemorySize / 1024 / 1024, 2);
				$free = round($raw->FreePhysicalMemory / 1024 / 1024, 2);
				$used = $total - $free;
				$data = [
					'used' => (float) round($used, 2),
					'total' => (float) $total,
					'percentage' => (float) round(($used / $total) * 100, 2)
				];
			}
		} else {
			$free = shell_exec('free -b');
			$lines = explode("\n", trim($free));
			if (count($lines) >= 2) {
				$mem = preg_split('/\s+/', $lines[1]);
				$total = $mem[1];
				$used = $mem[2];
				$data = [
					'used' => round($used / 1024 ** 3, 2),
					'total' => round($total / 1024 ** 3, 2),
					'percentage' => round(($used / $total) * 100, 2)
				];
			}
		}

		return response()->json([
			'success' => true,
			'data' => $data
		]);
	}

	public function getDisk()
	{
		$path = PHP_OS_FAMILY === 'Windows' ? 'C:' : '/';
		$total = disk_total_space($path);
		$free = disk_free_space($path);
		$used = $total - $free;

		$data = [
			'used' => round($used / 1024 ** 3, 2),
			'total' => round($total / 1024 ** 3, 2),
			'percentage' => round(($used / $total) * 100, 2)
		];

		return response()->json([
			'success' => true,
			'data' => $data
		]);
	}

	public function getServices()
	{
		$isWindows = PHP_OS_FAMILY === 'Windows';
		$services = [
			// --- WEB SERVERS ---
			['label' => 'Apache', 'win' => 'httpd.exe', 'linux' => 'apache2', 'icon' => 'i-simple-icons-apache'],
			['label' => 'Nginx', 'win' => 'nginx.exe', 'linux' => 'nginx', 'icon' => 'i-simple-icons-nginx'],
			['label' => 'PHP-FPM', 'win' => 'php-cgi.exe', 'linux' => 'php-fpm', 'icon' => 'i-simple-icons-php'],

			// --- RELATIONAL DATABASES (SQL) ---
			['label' => 'MySQL', 'win' => 'mysqld.exe', 'linux' => 'mysql', 'icon' => 'i-simple-icons-mysql'],
			['label' => 'MariaDB', 'win' => 'mariadbd.exe', 'linux' => 'mariadb', 'icon' => 'i-simple-icons-mariadb'],
			['label' => 'PostgreSQL', 'win' => 'postgres.exe', 'linux' => 'postgres', 'icon' => 'i-simple-icons-postgresql'],
			[
				'label' => 'SQL Server',
				'win' => 'sqlservr.exe',
				'linux' => 'mssql-server',
				'icon' => 'i-simple-icons-microsoftsqlserver'
			],

			// --- NOSQL & DOCUMENT DATABASES ---
			['label' => 'MongoDB', 'win' => 'mongod.exe', 'linux' => 'mongod', 'icon' => 'i-simple-icons-mongodb'],
			[
				'label' => 'Cassandra',
				'win' => 'cassandra.exe',
				'linux' => 'cassandra',
				'icon' => 'i-simple-icons-apachecassandra'
			],

			// --- IN-MEMORY & CACHING ---
			['label' => 'Redis', 'win' => 'redis-server.exe', 'linux' => 'redis-server', 'icon' => 'i-simple-icons-redis'],
			['label' => 'Memcached', 'win' => 'memcached.exe', 'linux' => 'memcached', 'icon' => 'i-simple-icons-memcached']
		];

		$status = [];
		foreach ($services as $row) {
			$name = $isWindows ? $row['win'] : $row['linux'];
			if ($isWindows) {
				$isRunning = strpos(shell_exec("tasklist /NH /FI \"IMAGENAME eq $name\""), $name) !== false;
			} else {
				$isRunning = !empty(trim(shell_exec("pgrep -x $name")));
			}
			$status[] = ['name' => $row['label'], 'status' => $isRunning];
		}

		return response()->json([
			'success' => true,
			'data' => $status
		]);
	}

	public function getLogs()
	{
		$path = storage_path('logs/laravel.log');
		if (!File::exists($path)) {
			return response()->json(['success' => true, 'data' => []]);
		}

		$lines = array_reverse(explode("\n", File::get($path)));
		$logs = [];
		$pattern = '/^\[(?<date>.*)\]\s(?<env>\w+)\.(?<level>\w+):(?<message>.*)/';

		foreach ($lines as $line) {
			if (preg_match($pattern, $line, $matches)) {
				$logs[] = [
					'time' => Carbon::parse($matches['date'])->format('H:i:s'),
					'level' => strtolower($matches['level']),
					'message' => trim($matches['message'])
				];
				if (count($logs) >= 9) {
					break;
				}
			}
		}

		return response()->json([
			'success' => true,
			'data' => $logs
		]);
	}
}
