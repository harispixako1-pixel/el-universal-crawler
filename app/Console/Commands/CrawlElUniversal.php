<?php

namespace App\Console\Commands;

use App\Services\Crawler\ElUniversalCrawler;
use Illuminate\Console\Command;

class CrawlElUniversal extends Command
{
    protected $signature = 'crawler:run {--limit=10}';

    protected $description = 'Crawl articles from El Universal';

    public function handle(ElUniversalCrawler $crawler): int
    {
        $limit = max(
            1,
            (int) $this->option('limit')
        );

        $this->info(
            'Starting El Universal crawler...'
        );

        $results = $crawler->crawl($limit);

        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                [
                    'URLs discovered',
                    $results['discovered'],
                ],
                [
                    'Articles saved',
                    $results['saved'],
                ],
                [
                    'Articles skipped',
                    $results['skipped'],
                ],
                [
                    'Articles failed',
                    $results['failed'],
                ],
            ]
        );

        $this->newLine();

        $this->info('Crawler finished.');

        return self::SUCCESS;
    }
}