<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Keeps active list filters when a form redirects back to its originating list.
 */
final class PreserveListFiltersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (
            strtoupper($request->getMethod()) !== 'POST'
            || $request->isAJAX()
            || $response->getStatusCode() < 300
            || $response->getStatusCode() >= 400
        ) {
            return $response;
        }

        $location = $response->getHeaderLine('Location');
        $referer = $request->getHeaderLine('Referer');
        if ($location === '' || $referer === '') {
            return $response;
        }

        $target = parse_url($location);
        $source = parse_url($referer);
        if ($target === false || $source === false || empty($source['query'])) {
            return $response;
        }

        $requestHost = strtolower($request->getUri()->getHost());
        foreach ([$target, $source] as $url) {
            if (isset($url['host']) && strtolower($url['host']) !== $requestHost) {
                return $response;
            }
        }

        $targetPath = rtrim((string) ($target['path'] ?? ''), '/') ?: '/';
        $sourcePath = rtrim((string) ($source['path'] ?? ''), '/') ?: '/';
        if ($targetPath !== $sourcePath) {
            return $response;
        }

        parse_str((string) $source['query'], $sourceParameters);
        parse_str((string) ($target['query'] ?? ''), $targetParameters);
        $query = http_build_query(
            array_merge($sourceParameters, $targetParameters),
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
        if ($query === '') {
            return $response;
        }

        $fragment = '';
        $locationWithoutFragment = $location;
        if (str_contains($location, '#')) {
            [$locationWithoutFragment, $fragmentValue] = explode('#', $location, 2);
            $fragment = '#' . $fragmentValue;
        }
        $locationWithoutQuery = explode('?', $locationWithoutFragment, 2)[0];

        return $response->setHeader('Location', $locationWithoutQuery . '?' . $query . $fragment);
    }
}
