<?php

namespace App\Services;

use App\Support\Helper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Scraper {
  protected $client;
  private ?Collection $urls = null;
  private ?string $startsWith = null;

  public function __construct()
  {
    $this->client = app()->make("Goutte\Client");
  }

  public function setUrls(?string $site) {
    $urls = $this->getSitemapUrls();
    $this->urls = $urls->filter(function ($url) {
      if ($this->startsWith) {
        return $this->startsWith($this->startsWith, $url);
      }
      return true;
    })->map(function ($url) {
      $convertSitemapUrls = config('settings.convert-sitemap-urls-to-local');
      $productionDomain = config('settings.production.domain');
      $localDomain = config('settings.local.domain');
      if ($convertSitemapUrls && (!$productionDomain || !$localDomain)) {
        dd('Specify both domains in order to use the convert sitemap urls feature.');
      }
      if ($convertSitemapUrls) {
        return Str::replace($productionDomain, $localDomain, $url);
      }
      return $url;
    });
  }

  private function startsWith(string $string, string $url): bool {
    $string = trim(strtolower(Str::replace('/', '', $string)));
		return Str::endsWith($url, "/" . $string) || Str::contains($url, "/" . $string . "/");
  }

  public function getUrls() {
    return $this->urls;
  }

  public function setStartsWithFilter(string $string) {
    $this->startsWith = $string;
  }

  private function getSitemapUrls(): Collection {
    $sitemap_url = config('settings.sitemap.url');
    if (!$sitemap_url) {
      dd('No sitemap defined');
    }
    $urls = [];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sitemap_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec ($ch);
    curl_close ($ch);

    $xml = new \SimpleXMLElement($data);
    foreach ($xml->url as $url_list) {
        $urls[] = (string)$url_list->loc;
    }
    return collect($urls);
  }

  public function getDataBySelector(string $url, string $selector, array $attrs = [], array $relationAttrs = [], bool $showRelations = false): Collection {
    $crawler = $this->client->request('GET', $url);
    $headings = collect(['URL', 'Element', 'Text']);
    foreach($attrs as $attr) {
      $headings->push("Attr: " . $attr);
    };
    if ($showRelations) {
      $headings = $headings->merge(['Parent elements', 'Parent element attrs', 'Child elements', 'Child element attrs']);
    }
    $values = collect();
    $values = $values->merge($crawler->filter($selector)->each(function ($node) use ($attrs, $url, $relationAttrs, $showRelations) {
      $values = collect([
        $url,
        $node->nodeName(),
        $node->text()
      ]);
      foreach($attrs as $attr) {
        $values->push($node->attr($attr));
      };
      if ($showRelations) {
        $values = $values->merge([
          $node->ancestors()->count() > 0 ? implode(', ', $node->ancestors()->each(function($node) use ($attrs) {
            return $node->nodeName();
          })) : '',
          $node->ancestors()->count() > 0 ? implode(', ', $node->ancestors()->each(function($node) use ($relationAttrs) {
            return $this->nodeAttrsToString($node, $relationAttrs);
          })) : '',
          $node->children()->count() > 0 ? implode(', ', $node->children()->each(function($node) use ($attrs) {
            return $node->nodeName();
          })) : '',
          $node->children()->count() > 0 ? implode(', ', $node->children()->each(function($node) use ($relationAttrs) {
            return $this->nodeAttrsToString($node, $relationAttrs);
          })) : ''
        ]);
      }
      return $values->toArray();
    }));
    return collect([
      'headings' => $headings->toArray(),
      'values' => $values->toArray()
    ]);
  }

  private function nodeAttrsToString($node, array $attrs)
  {
    return trim(collect($attrs)->map(function($attr) use ($node) {
      return $node->attr($attr) ? $node->attr($attr) : '-';
    })->join(' '));
  }
}
