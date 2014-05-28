<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Seo_Frontend extends BClass
{
    static public function bootstrap()
    {
        static::rememberReferrer();
    }

    static public function rememberReferrer()
    {
        $referrer =& BSession::i()->dataToUpdate('referrer');
        if (empty($referrer)) {
            $url = BRequest::i()->referrer();
            if (!$url) {
                $referrer = [
                    'url' => null,
                ];
            } else {
                $parts = parse_url($url);
                $source = null;
                $keywords = null;
                $searchEngines = 'google|yahoo|yandex|baidu|bing|ask|aol|alltheweb|duckduckgo|startpage|ixquick';
                if (preg_match('/\b(' . $searchEngines . ')\.com$/', $parts['host'], $match)) {
                    $source = $match[1];
                }
                if ($source && !empty($parts['query'])) {
                    $query = parse_str($parts['query']);
                    switch ($source) {
                        case 'google': case 'bing': case 'ask': case 'aol': case 'alltheweb': case 'duckduckgo':
                            $keywords = !empty($query['q']) ? $query['q'] : null;
                            break;
                        case 'yahoo':
                            $keywords = !empty($query['p']) ? $query['p'] : null;
                            break;
                        case 'baidu':
                            $keywords = !empty($query['wd']) ? $query['wd'] : null;
                            break;
                        case 'yandex':
                            $keywords = !empty($query['text']) ? $query['text'] : null;
                            break;
                    }
                    if ($keywords) {
                        $keywords = preg_split('/\W+/', $keywords);
                    }
                }
                $referrer = [
                    'url' => $url,
                    'source_host' => $parts['host'],
                    'source' => $source,
                    'keywords' => $keywords,
                ];
            }
        }
    }
}
