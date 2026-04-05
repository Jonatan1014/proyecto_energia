<?php
// src/app/Services/SoketiService.php

class SoketiService {
    public function buildAuth(string $socketId, string $channelName, ?array $presenceData = null): array {
        if ($presenceData !== null) {
            $channelData = json_encode($presenceData, JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $socketId . ':' . $channelName . ':' . $channelData, SOKETI_APP_SECRET);
            return [
                'auth' => SOKETI_APP_KEY . ':' . $signature,
                'channel_data' => $channelData,
            ];
        }

        $signature = hash_hmac('sha256', $socketId . ':' . $channelName, SOKETI_APP_SECRET);
        return [
            'auth' => SOKETI_APP_KEY . ':' . $signature,
        ];
    }

    public function publish(string $channel, string $eventName, array $payload): bool {
        $path = '/apps/' . rawurlencode(SOKETI_APP_ID) . '/events';
        $body = [
            'name' => $eventName,
            'channels' => [$channel],
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        $bodyMd5 = md5($jsonBody);
        $timestamp = time();

        $queryParams = [
            'auth_key' => SOKETI_APP_KEY,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
            'body_md5' => $bodyMd5,
        ];

        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = 'POST' . "\n" . $path . "\n" . $queryString;
        $signature = hash_hmac('sha256', $stringToSign, SOKETI_APP_SECRET);
        $queryString .= '&auth_signature=' . $signature;

        $url = SOKETI_SCHEME . '://' . SOKETI_HOST . ':' . SOKETI_PORT . $path . '?' . $queryString;

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $jsonBody,
                'timeout' => 3,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }
}
