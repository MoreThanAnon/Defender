<?php

namespace App\Console\Commands;

use App\Services\DecidimPocService;
use Illuminate\Console\Command;

class TestDecidimCommand extends Command
{
    protected $signature = 'decidim:test';
    protected $description = 'Proof-of-concept connection test between Laravel and Decidim';

    public function handle(DecidimPocService $decidim): int
    {
        try {
            $result = $decidim->run();
        } catch (\Throwable $e) {
            $this->error('Decidim test failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connected to Decidim');
        $this->line('Organization: '.($result['organization']['name'] ?? 'unknown'));
        $this->line('Version: '.$result['decidim']['version']);
        $this->line('Assembly: '.($result['created_assembly']['title'] ?? 'TEST HOTEL GOVERNANCE'));
        $this->line('ID: '.($result['created_assembly']['assembly_id'] ?? 'unknown'));

        return self::SUCCESS;
    }
}
