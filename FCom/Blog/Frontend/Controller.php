<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Blog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss(BApp::href('blog/feed.rss'));
        $this->layout('/blog/index');
    }

    public function action_tag()
    {
        $tagName = BRequest::i()->param('tag');
        if ($tagName) {
            $tag = FCom_Blog_Model_Tag::i()->load($tagName, 'tag_name');
            if (!$tag) {
                $this->forward(false);
                return;
            }
        }
        $this->view('head')->rss($tag->getUrl() . '/feed.rss');
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->join('FCom_Blog_Model_PostTag', ['pt.post_id', '=', 'p.id'], 'pt')
            ->where('pt.tag_id', $tag->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($tagName);
        $this->layout('/blog/tag');
    }

    public function action_category()
    {
        $catName = BRequest::i()->param('category');
        if ($catName) {
            $cat = FCom_Blog_Model_Category::i()->load($catName, 'url_key');
            if (!$cat) {
                $this->forward(false);
                return;
            }
        }
        $this->view('head')->rss($cat->getUrl() . '/feed.rss');
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->join('FCom_Blog_Model_PostCategory', ['pc.post_id', '=', 'p.id'], 'pc')
            ->where('pc.category_id', $cat->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($cat->name);
        $this->layout('/blog/category');
    }

    public function action_author()
    {
        $userName = BRequest::i()->param('user');
        if ($userName) {
            $user = FCom_Admin_Model_User::i()->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
                return;
            }
        }
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->where('p.author_user_id', $user->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss(BApp::href('blog') . '/author/' . $userName . '/feed.rss');
        $this->view('head')->addTitle($user->firstname . ' ' . $user->lastname);
        $this->layout('/blog/author');
    }

    public function action_archive()
    {
        $r = BRequest::i();
        $y = $r->param('year');
        if (!$y) {
            $this->forward(false);
            return;
        }
        $m = $r->param('month');
        $postsOrm = FCom_Blog_Model_Post::i()->getPostsOrm();
        if ($m) {
            $postsOrm->where('create_ym', $y . $m);
            $this->view('head')->addTitle($y . '/' . $m);
        } else {
            $postsOrm->where_like('create_ym', $y . '%');
            $this->view('head')->addTitle($y);
        }
        $this->view('blog/posts')->set('posts', $postsOrm->find_many());
        $this->layout('/blog/archive');
    }

    public function action_post()
    {
        $postKey = BRequest::i()->param('post');
        // allow "2013/08/05/post-url-key" format
        if (preg_match('#^([0-9]{4})/([0-9]{2})/([0-9]{2})/(.*)#', $postKey, $m)) {
            $postKey = $m[4];
        }
        $post = FCom_Blog_Model_Post::i()->load($postKey, 'url_key');
        $adminUserId = FCom_Admin_Model_User::i()->sessionUserId();
        if (!($post && (
            $post->get('status') === 'published'
            || $adminUserId && $adminUserId === $post->get('author_user_id')
            || BRequest::i()->get('preview')
        ))) {
            $this->forward(false);
            return;
        }
        $this->view('head')->canonical($post->getUrl());
        $this->view('blog/post')->set('post', $post);
        $this->view('head')->addTitle($post->get('title'));
        $this->view('head')->addMeta('title', $post->get('meta_title'));
        $this->view('head')->addMeta('description', $post->get('meta_description'));
        $this->view('head')->addMeta('keywords', $post->get('meta_keywords'));
        $this->layout('/blog/post');
    }

    public function action_rss()
    {
        $postsOrm = FCom_Blog_Model_Post::i()->getPostsOrm();

        $tagKey = BRequest::i()->param('tag');
        if ($tagKey) {
            $tag = FCom_Blog_Model_Tag::i()->load($tagKey, 'tag_key');
            if (!$tag) {
                $this->forward(false);
                return;
            }
            $postsOrm
                ->join('FCom_Blog_Model_PostTag', ['pt.post_id', '=', 'p.id'], 'pt')
                ->where('pt.tag_id', $tag->id());
        }

        $catKey = BRequest::i()->param('category');
        if ($catKey) {
            $cat = FCom_Blog_Model_Category::i()->load($catKey, 'url_key');
            if (!$cat) {
                $this->forward(false);
                return;
            }
            $postsOrm
                ->join('FCom_Blog_Model_PostCategory', ['pc.post_id', '=', 'p.id'], 'pc')
                ->where('pc.category_id', $cat->id());
        }

        $userName = BRequest::i()->param('user');
        if ($userName) {
            $user = FCom_Admin_Model_User::i()->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
                return;
            }
            $postsOrm->where('author_user_id', $user->id());
        }
        $data = [
            'title' => BConfig::i()->get('modules/FCom_Blog/blog_title'),
            'link' => $tagKey ? $tag->getUrl() : BApp::href('blog'),
            'items' => [],
        ];
        foreach ($postsOrm->find_many() as $p) {
            $data['items'][] = [
                'link' => $p->getUrl(),
                'pubDate' => date('D, d M Y H:i:s O', strtotime($p->create_at)),
                'title' => $p->title,
                'description' => $p->preview,
            ];
        }
        echo BUtil::toRss($data);
        exit;
    }
}
