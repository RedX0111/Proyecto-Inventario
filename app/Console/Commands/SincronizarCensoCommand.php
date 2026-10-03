<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SincronizarCensoCommand extends Command
{
    protected $signature = 'censo:sync';
    protected $description = 'Jala la data de Google Sheets (Master + Historico) y la inyecta en MariaDB';

    public function handle()
    {
        $url = env('GOOGLE_SHEETS_WEBAPP_URL');

        if (!$url) {
            $this->error("❌ Configura GOOGLE_SHEETS_WEBAPP_URL en tu .env");
            return 1;
        }

        $this->info("📡 Extrayendo datos unificados de Google Sheets (Master + Histórico)...");

        try {
            // Jalar el JSON unificado
            $response = Http::withoutVerifying()->timeout(120)->get($url);

            if (!$response->successful()) {
                $this->error("❌ Error HTTP ({$response->status()}) conectando al Apps Script.");
                return 1;
            }

            $json = $response->json();

            if (!isset($json['data']) || count($json['data']) === 0) {
                $this->warn("⚠️ La hoja de histórico está vacía. No hay datos que migrar.");
                return 0;
            }

            $items = $json['data'];
            $total = count($items);
            $this->info("📦 Se leyeron {$total} censos. Ingiriendo en base de datos BCNF...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($items as $item) {
                // Convertimos los strings vacíos a null para que MariaDB no falle en columnas numéricas
                $orden = (trim($item['orden']) === '') ? null : (int) $item['orden'];

                DB::statement('CALL sp_bd_ingestar_datos_campo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $orden,
                    $item['codigo'],
                    $item['denominacion'],
                    $item['marca'],
                    $item['modelo'],
                    $item['categoria'],
                    $item['color'],
                    $item['serie'],
                    $item['dimensiones'],
                    $item['fecha'],
                    $item['estado'],
                    $item['ubicacion'],
                    $item['observaciones'],
                    $item['dni_operador']
                ]);

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);
            $this->info("✅ ¡Sincronización completada! Los datos se asignaron a los catálogos y a la bandeja de limpieza.");

            return 0;

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->newLine();
            $this->error("❌ Error Fatal durante la ingesta: " . $th->getMessage());
            return 1;
        }
    }
}