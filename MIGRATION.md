6/18/2014
=========

* For themes: edit manifest.yml, remove `provides:`, and move `themes:` one level up
* Rename `themes/*/layout` to `themes/*/layout_before`
    * Added `layout_after` for layout updates after layouts from other modules have been loaded
    * Similarly, `views_before` and `views_after` were added
* Move `views/static/index.html.twig` to `views/index.html.twig`