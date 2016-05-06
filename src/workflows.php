<?php

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Workflows
 */
class Workflows
{

    private $baseUrl = 'https://laracasts.com/';

    /**
     * @param $query
     * @return mixed
     */
    public function search($query)
    {
        $html    = $this->getHtml($query);
        $results = $this->getResults($html);

        return $this->parseResults($results)->asXML();
    }

    /**
     * @param $query
     * @return string
     */
    private function getUrl($query)
    {
        return $query == 'latest' ? $this->latestUrl() : $this->searchUrl($query);
    }

    /**
     * @param $query
     * @return string
     */
    private function getHtml($query)
    {
        $html = file_get_contents($this->getUrl($query));

        return $html;
    }

    /**
     * @param $html
     */
    private function getResults($html)
    {
        $parser  = new Crawler($html);
        $results = [];

        $parser->filter('span.Lesson-List__title')->each(function (Crawler $node) use (&$results) {

            $link = $node->children()->attr('href');

            if ($lesson = $this->getEpisode($link)) {
                $results[] = $lesson;
            } else {

                if ($lesson = $this->getSeriesEpisode($node)) {
                    $results[] = $lesson;
                }
            }
        });

        return $results;
    }


    /**
     * @param Crawler $node
     * @return array|bool
     */
    private function getSeriesEpisode(Crawler $node)
    {
        $link = $node->children()->eq(1)->attr('href');

        if (preg_match('/series\/(.+)\/episodes\/(\d+)/', $link, $matches)) {
            return [
                'slug'    => $matches[1],
                'type'    => 'series',
                'episode' => $matches[2],
                'series'  => trim($node->children()->eq(1)->text())
            ];
        }

        return false;
    }

    /**
     * @param $results
     */
    private function parseResults($results)
    {
        $items = new SimpleXMLElement('<items></items>');

        foreach ($results as $result) {
            $item = $items->addChild('item');

            if ($result['type'] == 'lesson') {
                $item->title = ucfirst(str_replace('-', ' ', $result['slug']));
                $item->addAttribute('arg', $this->episodeUrl($result));
            } else {
                $item->addAttribute('arg', $this->seriesUrl($result));
                $item->title    = $result['series'];
                $item->subtitle = ucfirst(str_replace('-', ' ', $result['slug']));
            }
        }

        return $items;
    }

    /**
     * @param $link
     * @return array|bool
     */
    private function getEpisode($link)
    {
        if (preg_match('/lessons\/(.+)/', $link, $matches)) {
            return ['slug' => $matches[1], 'type' => 'lesson'];
        }

        return false;
    }

    /**
     * @return string
     */
    private function latestUrl()
    {
        return sprintf('%slessons', $this->baseUrl);
    }

    /**
     * @param $query
     * @return string
     */
    private function searchUrl($query)
    {
        return sprintf('%ssearch?q=%s&q-where=lessons', $this->baseUrl, $query);
    }

    /**
     * @param $result
     * @return string
     */
    private function seriesUrl($result)
    {
        return sprintf('https://laracasts.com/series/%s/episodes/%s', $result['slug'], $result['episode']);
    }

    /**
     * @param $result
     * @return string
     */
    private function episodeUrl($result)
    {
        return sprintf('https://laracasts.com/lessons/%s', $result['slug']);
    }
}