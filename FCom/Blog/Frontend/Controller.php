<?php

class FCom_Blog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/blog/index');
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss(BApp::href('blog/feed.rss'));
    }

    public function action_tag()
    {
        $this->layout('/blog/tag');
        $tagName = BRequest::i()->param('tag');
        if ($tagName) {
            $tag = FCom_Blog_Model_Tag::i()->load($tagName, 'tag_name');
            if (!$tag) {
                $this->forward(false);
            }
        }
        $this->view('head')->rss($tag->getUrl().'/feed.rss');
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->join('FCom_Blog_Model_PostTag', array('pt.post_id','=','p.id'), 'pt')
            ->where('pt.tag_id', $tag->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($tagName);
    }

    public function action_category()
    {
        $this->layout('/blog/category');
        $catName = BRequest::i()->param('category');
        if ($catName) {
            $cat = FCom_Blog_Model_Category::i()->load($catName, 'url_key');
            if (!$cat) {
                $this->forward(false);
            }
        }
        $this->view('head')->rss($cat->getUrl().'/feed.rss');
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->join('FCom_Blog_Model_PostCategory', array('pc.post_id','=','p.id'), 'pc')
            ->where('pc.category_id', $cat->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($cat->name);
    }

    public function action_author()
    {
        $this->layout('/blog/author');
        $userName = BRequest::i()->param('user');
        if ($userName) {
            $user = FCom_Admin_Model_User::i()->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
            }
        }
        $posts = FCom_Blog_Model_Post::i()->getPostsOrm()
            ->where('p.author_user_id', $user->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss(BApp::href('blog').'/author/'.$userName.'/feed.rss');
        $this->view('head')->addTitle($user->firstname.' '.$user->lastname);
    }

    public function action_archive()
    {
        $this->layout('/blog/archive');
        $r = BRequest::i();
        $y = $r->param('year');
        if (!$y) {
            $this->forward(false);
            return;
        }
        $m = $r->param('month');
        $postsOrm = FCom_Blog_Model_Post::i()->getPostsOrm();
        if ($m) {
            $postsOrm->where('create_ym', $y.$m);
            $this->view('head')->addTitle($y.'/'.$m);
        } else {
            $postsOrm->where_like('create_ym', $y.'%');
            $this->view('head')->addTitle($y);
        }
        $this->view('blog/posts')->set('posts', $postsOrm->find_many());
    }

    public function action_post()
    {
        $this->layout('/blog/post');
        $postKey = BRequest::i()->param('post');
        // allow "2013/08/05/post-url-key" format
        if (preg_match('#^([0-9]{4})/([0-9]{2})/([0-9]{2})/(.*)#', $postKey, $m)) {
            $postKey = $m[4];
        }
        $post = FCom_Blog_Model_Post::i()->load($postKey, 'url_key');
        if (!$post) {
            $this->forward(false);
            return;
        }
        $this->view('head')->canonical($post->getUrl());
        $this->view('blog/post')->set('post', $post);
        $this->view('head')->addTitle($post->get('meta_tile') ? $post->get('meta_tile') : $post->get('title'));
        $this->view('head')->addMeta('description', $post->get('meta_keywords'));
        $this->view('head')->addMeta('keywords', $post->get('meta_keywords'));
    }

    public function action_rss()
    {
        $postsOrm = FCom_Blog_Model_Post::i()->getPostsOrm();

        $tagKey = BRequest::i()->param('tag');
        if ($tagKey) {
            $tag = FCom_Blog_Model_Tag::i()->load($tagKey, 'tag_key');
            if (!$tag) {
                $this->forward(false);
            }
            $postsOrm
                ->join('FCom_Blog_Model_PostTag', array('pt.post_id','=','p.id'), 'pt')
                ->where('pt.tag_id', $tag->id());
        }

        $catKey = BRequest::i()->param('category');
        if ($catKey) {
            $cat = FCom_Blog_Model_Category::i()->load($catKey, 'url_key');
            if (!$cat) {
                $this->forward(false);
            }
            $postsOrm
                ->join('FCom_Blog_Model_PostCategory', array('pc.post_id','=','p.id'), 'pc')
                ->where('pc.category_id', $cat->id());
        }

        $userName = BRequest::i()->param('user');
        if ($userName) {
            $user = FCom_Admin_Model_User::i()->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
            }
            $postsOrm->where('author_user_id', $user->id());
        }
        $data = array(
            'title' => BConfig::i()->get('modules/FCom_Blog/blog_title'),
            'link' => $tagKey ? $tag->getUrl() : BApp::href('blog'),
            'items' => array(),
        );
        foreach ($postsOrm->find_many() as $p) {
            $data['items'][] = array(
                'link' => $p->getUrl(),
                'pubDate' => date('D, d M Y H:i:s O', strtotime($p->create_at)),
                'title' => $p->title,
                'description' => $p->preview,
            );
        }
        echo BUtil::toRss($data);
        exit;
    }
}
