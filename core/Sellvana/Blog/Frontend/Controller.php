<?php

/**
 * Class Sellvana_Blog_Frontend_Controller
 *
 * @property Sellvana_Blog_Model_Post $Sellvana_Blog_Model_Post
 * @property Sellvana_Blog_Model_Tag $Sellvana_Blog_Model_Tag
 * @property Sellvana_Blog_Model_Category $Sellvana_Blog_Model_Category
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class Sellvana_Blog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $posts = $this->Sellvana_Blog_Model_Post->getPostsOrm()->find_many();
        $this->layout('/blog/index');
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss($this->BApp->href('blog/feed.rss'));
    }

    public function action_tag()
    {
        $tagName = $this->BRequest->param('tag');
        if ($tagName) {
            $tag = $this->Sellvana_Blog_Model_Tag->load($tagName, 'tag_name');
            if (!$tag) {
                $this->forward(false);
                return;
            }
        }
        $this->layout('/blog/tag');
        $this->view('head')->rss($tag->getUrl() . '/feed.rss');
        $posts = $this->Sellvana_Blog_Model_Post->getPostsOrm()
            ->join('Sellvana_Blog_Model_PostTag', ['pt.post_id', '=', 'p.id'], 'pt')
            ->where('pt.tag_id', $tag->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($tagName);
    }

    public function action_category()
    {
        $catName = $this->BRequest->param('category');
        if ($catName) {
            $cat = $this->Sellvana_Blog_Model_Category->load($catName, 'url_key');
            if (!$cat) {
                $this->forward(false);
                return;
            }
        }
        $this->layout('/blog/category');
        $this->view('head')->rss($cat->getUrl() . '/feed.rss');
        $posts = $this->Sellvana_Blog_Model_Post->getPostsOrm()
            ->join('Sellvana_Blog_Model_PostCategory', ['pc.post_id', '=', 'p.id'], 'pc')
            ->where('pc.category_id', $cat->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->addTitle($cat->name);
    }

    public function action_author()
    {
        $userName = $this->BRequest->param('user');
        if ($userName) {
            $user = $this->FCom_Admin_Model_User->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
                return;
            }
        }
        $this->layout('/blog/author');
        $posts = $this->Sellvana_Blog_Model_Post->getPostsOrm()
            ->where('p.author_user_id', $user->id)
            ->find_many();
        $this->view('blog/posts')->set('posts', $posts);
        $this->view('head')->rss($this->BApp->href('blog') . '/author/' . $userName . '/feed.rss');
        $this->view('head')->addTitle($user->firstname . ' ' . $user->lastname);
    }

    public function action_archive()
    {
        $r = $this->BRequest;
        $y = $r->param('year');
        if (!$y) {
            $this->forward(false);
            return;
        }
        $m = $r->param('month');
        $postsOrm = $this->Sellvana_Blog_Model_Post->getPostsOrm();
        $this->layout('/blog/archive');
        if ($m) {
            $postsOrm->where('create_ym', $y . $m);
            $this->view('head')->addTitle($y . '/' . $m);
        } else {
            $postsOrm->where_like('create_ym', $y . '%');
            $this->view('head')->addTitle($y);
        }
        $this->view('blog/posts')->set('posts', $postsOrm->find_many());
    }

    public function action_post()
    {
        $postKey = $this->BRequest->param('post');
        // allow "2013/08/05/post-url-key" format
        if (preg_match('#^([0-9]{4})/([0-9]{2})/([0-9]{2})/(.*)#', $postKey, $m)) {
            $postKey = $m[4];
        }
        $post = $this->Sellvana_Blog_Model_Post->load($postKey, 'url_key');
        $adminUserId = $this->FCom_Admin_Model_User->sessionUserId();
        if (!($post && (
            $post->get('status') === 'published'
            || $adminUserId && $adminUserId === $post->get('author_user_id')
            || $this->BRequest->get('preview')
        ))) {
            $this->forward(false);
            return;
        }
        $this->layout('/blog/post');
        $this->view('head')->canonical($post->getUrl());
        $this->view('blog/post')->set('post', $post);
        $this->view('head')->addTitle($post->get('title'));
        $this->view('head')->addMeta('title', $post->get('meta_title'));
        $this->view('head')->addMeta('description', $post->get('meta_description'));
        $this->view('head')->addMeta('keywords', $post->get('meta_keywords'));
    }

    public function action_rss()
    {
        $postsOrm = $this->Sellvana_Blog_Model_Post->getPostsOrm();

        $tagKey = $this->BRequest->param('tag');
        if ($tagKey) {
            $tag = $this->Sellvana_Blog_Model_Tag->load($tagKey, 'tag_key');
            if (!$tag) {
                $this->forward(false);
                return;
            }
            $postsOrm
                ->join('Sellvana_Blog_Model_PostTag', ['pt.post_id', '=', 'p.id'], 'pt')
                ->where('pt.tag_id', $tag->id());
        }

        $catKey = $this->BRequest->param('category');
        if ($catKey) {
            $cat = $this->Sellvana_Blog_Model_Category->load($catKey, 'url_key');
            if (!$cat) {
                $this->forward(false);
                return;
            }
            $postsOrm
                ->join('Sellvana_Blog_Model_PostCategory', ['pc.post_id', '=', 'p.id'], 'pc')
                ->where('pc.category_id', $cat->id());
        }

        $userName = $this->BRequest->param('user');
        if ($userName) {
            $user = $this->FCom_Admin_Model_User->load($userName, 'username');
            if (!$user) {
                $this->forward(false);
                return;
            }
            $postsOrm->where('author_user_id', $user->id());
        }
        $data = [
            'title' => $this->BConfig->get('modules/Sellvana_Blog/blog_title'),
            'link' => $tagKey ? $tag->getUrl() : $this->BApp->href('blog'),
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
        echo $this->BUtil->toRss($data);
        exit;
    }
}
