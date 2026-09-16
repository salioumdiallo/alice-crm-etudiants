<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleSearchService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $googleApiKey,
        private string $googleCustomSearchApiKey
    ) {
    }

    public function findWebsiteRank(string $keyword, string $websiteLink): ?int
    {
        $response = $this->httpClient->request(
            'GET',
            'https://www.googleapis.com/customsearch/v1',
            [
                'query' => [
                    'key' => $this->googleApiKey,
                    'cx' => $this->googleCustomSearchApiKey,
                    'q' => $keyword,
                ],
            ]
        );

        $content = $response->toArray();

        foreach ($content['items'] ?? [] as $index => $item) {
            if (
                isset($item['link'])
                && stripos($item['link'], $websiteLink) !== false
            ) {
                return $index + 1;
            }
        }

        return null;
    }
}
