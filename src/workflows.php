<?php

/**
 * Class Workflows
 */
class Workflows {

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
    private function getHtml($query)
    {
        $html = file_get_contents(sprintf('https://laracasts.com/search?q=%s&q-where=lessons', $query));

        return $html;
    }

    /**
     * @param $html
     */
    private function getResults($html)
    {
        $parser  = new \Symfony\Component\DomCrawler\Crawler($html);
        $results = [];

        $parser->filter('span.Lesson-List__title')->each(function (\Symfony\Component\DomCrawler\Crawler $node) use (
            &$results
        ) {

            $link = $node->children()->attr('href');

            if (preg_match('/lessons\/(.+)/', $link, $matches)) {
                $lesson = ['slug' => $matches[1], 'type' => 'lesson'];
            } else {

                $link = $node->children()->eq(1)->attr('href');

                if (preg_match('/series\/(.+)\/episodes\/(\d+)/', $link, $matches)) {

                    $array['series'][$matches[1]][] = (int)$matches[2];

                    $lesson = [
                        'slug'    => $matches[1],
                        'type'    => 'series',
                        'episode' => $matches[2],
                        'series'  => trim($node->children()->eq(1)->text())
                    ];
                }
            }

            $results[] = $lesson;
        });

        return $results;
    }

    /**
     * @param $results
     */
    private function parseResults($results)
    {
        $items = new SimpleXMLElement('<items></items>');

        foreach ($results as $result) {
            $item        = $items->addChild('item');
            $item->title = ucfirst(str_replace('-', ' ', $result['slug']));

            if ($result['type'] == 'lesson') {
                $item->addAttribute('arg', sprintf('https://laracasts.com/lessons/%s', $result['slug']));
            } else {
                $item->addAttribute('arg',
                    sprintf('https://laracasts.com/series/%s/episodes/%s', $result['slug'], $result['episode']));
                $item->subtitle = $result['series'];
            }
        }

        return $items;
    }
}