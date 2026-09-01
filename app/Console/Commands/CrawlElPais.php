<?php

namespace App\Console\Commands;

use App\Services\Crawler\ElPaisCrawler;
use Illuminate\Console\Command;

class CrawlElPais extends Command
{
    protected $signature = 'crawler:el-pais {--limit=10}';

    protected $description = 'Crawl premium articles from El Pais Uruguay using ScrapingBee';

    public function handle(ElPaisCrawler $crawler): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->info('Starting El Pais premium crawler...');
        $this->newLine();

        $results = $crawler->crawl($limit);

        if ($results['error']) {
            $this->error("Error: {$results['error']}");
            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['URLs discovered', $results['discovered']],
                ['Articles saved', $results['saved']],
                ['Articles skipped', $results['skipped']],
                ['Articles failed', $results['failed']],
            ]
        );

        $this->newLine();
        $this->info('El Pais crawler finished.');

        return self::SUCCESS;
    }
}
