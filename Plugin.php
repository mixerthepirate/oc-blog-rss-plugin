<?php namespace HolidayPirates\RssFeed;

use HolidayPirates\RssFeed\Components\Link;
use HolidayPirates\RssFeed\Models\Settings;
use Illuminate\Database\Eloquent\Collection;
use RainLab\Blog\Models\Category;
use RainLab\Blog\Models\Post;
use System\Classes\PluginBase;
use DB;
use DateTime;
use File;
use Controller;
use Event;
use Markdown;

/**
 * RssFeed Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.Blog'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'RssFeed',
            'description' => 'An RSS feed generator for the RainLab Blog plugin',
            'author'      => 'HolidayPirates',
            'icon'        => 'icon-rss'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return array
     */
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label'       => 'Blog RSS Settings',
                'description' => 'Manage the Blog RSS settings.',
                'category'    => 'Blog',
                'icon'        => 'icon-rss',
                'class'       => Settings::class,
                'order'       => 100
            ]
        ];
    }

    /**
     * Boot method, called right before the request route.
     */
    public function boot()
    {
        Event::listen('eloquent.created: RainLab\Blog\Models\Post', function ($model) {
            $this->createRss($this->loadPosts('press-releases-de'), 'rss.xml');
            $this->createRss($this->loadPosts('press-releases-en'), 'rss_en.xml');
        });
        Event::listen('eloquent.saved: RainLab\Blog\Models\Post', function ($model) {
            $this->createRss($this->loadPosts('press-releases-de'), 'rss.xml');
            $this->createRss($this->loadPosts('press-releases-en'), 'rss_en.xml');
        });
        Event::listen('eloquent.deleted: RainLab\Blog\Models\Post', function ($model) {
            $this->createRss($this->loadPosts('press-releases-de'), 'rss.xml');
            $this->createRss($this->loadPosts('press-releases-en'), 'rss_en.xml');
        });

        Event::listen('eloquent.saved: HolidayPirates\RssFeed\Models\Settings', function ($model) {
            $this->createRss($this->loadPosts('press-releases-de'), 'rss.xml');
            $this->createRss($this->loadPosts('press-releases-en'), 'rss_en.xml');
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            Link::class => 'rssLink',
        ];
    }

    /**
     * @param $posts
     * @param $fileName
     * @return mixed
     */
    protected function createRss($posts, $fileName)
    {
        $fileContents = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" .
            "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n".
            "\t<channel>\n".
            "\t\t<title>" . Settings::get('title') . "</title>\n" .
            "\t\t<link>" . Settings::get('link') . "</link>\n" .
            "\t\t<description>" . Settings::get('description') . "</description>\n".
            "\t\t<atom:link href=\"" . Settings::get('link') . "/rss.xml\" rel=\"self\" type=\"application/rss+xml\" />\n\n";

        foreach ($posts as $post) {
            $published = DateTime::createFromFormat('Y-m-d H:i:s', $post->published_at);
            $description = Settings::get('showFullPostContent') ? $post->content : $post->excerpt;
            $description = Markdown::parse(trim($description));
            $fileContents .= "\t\t<item>\n" .
                "\t\t\t<title>" . htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') . "</title>\n" .
                "\t\t\t<link>" . Settings::get('link') . Settings::get('postPage') . "/" . $post->slug . "</link>\n" .
                "\t\t\t<guid>" . Settings::get('link') . Settings::get('postPage') . "/" . $post->slug . "</guid>\n" .
                "\t\t\t<pubDate>" . $published->format(DateTime::RFC2822) . "</pubDate>\n" .
                "\t\t\t<description>" . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "</description>\n" .
                "\t\t</item>\n";
        }

        $fileContents .= "\t</channel>\n";
        $fileContents .= "</rss>\n";

        return File::put($fileName, $fileContents);
    }

    /**
     * @param $slug
     * @return Collection
     */
    protected function loadPosts($slug): Collection
    {
        $posts = Post::where('published', '=', '1')
            ->orderBy('published_at', 'desc')
            ->whereHas('categories', function ($q) use ($slug) {
                $q->where('category_id', '=', $this->getCategoryId($slug));
            })->get();

        return $posts;
    }

    /**
     * @param $slug
     * @return int
     */
    private function getCategoryId($slug): int
    {
        switch ($slug) {
            case 'press-releases-en':
                return Category::where('slug', 'press-releases-en')->first()->id;
                break;
            case 'press-releases-de':
                return Category::where('slug', 'press-releases-de')->first()->id;
                break;
        }
    }
}
