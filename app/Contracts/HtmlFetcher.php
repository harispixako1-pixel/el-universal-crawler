<?php

namespace App\Contracts;

interface HtmlFetcher
{
    public function fetch(string $url, array $params = []): string;
}
