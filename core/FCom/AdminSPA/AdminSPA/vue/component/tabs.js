define(['jquery', 'vue', 'text!sv-comp-tabs-tpl'], function ($, Vue, tabsTpl) {
    var SvCompFormTabs = {
        props: {
            'config': {type: Object},
            'container-class': {type: String, default: ''},
            'tab': {type: [Object, Boolean]}
        },
        template: tabsTpl,
        computed: {
            formTabs: function () {
                return this.config && this.config.tabs || [];
            }
        },
        methods: {
            switchTab: function (tab) {
                this.emitEvent('tab-switch', tab);
            }
        },
        mounted: function () {
            var $menuWrapper = $(this.$refs.menuWrapper);
            var $menu = $(this.$refs.menu);
            var $item = $(this.$refs.item);
            var $leftPaddle = $(this.$refs.leftPaddle);
            var $rightPaddle = $(this.$refs.rightPaddle);

            // duration of scroll animation
            var scrollDuration = 300;
// get items dimensions
            var itemsLength = this.formTabs.length;
            var itemSize = $item.outerWidth(true);
// get some relevant size for the paddle triggering point
            var paddleMargin = 20;

// get wrapper width
            var getMenuWrapperSize = function() {
                return $menuWrapper.outerWidth();
            };
            var menuWrapperSize = getMenuWrapperSize();
// the wrapper is responsive
            $(window).on('resize', function() {
                menuWrapperSize = getMenuWrapperSize();
            });
// size of the visible part of the menu is equal as the wrapper size
            var menuVisibleSize = menuWrapperSize;

// get total width of all menu items
            var getMenuSize = function() {
                return itemsLength * itemSize;
            };
            var menuSize = getMenuSize();
// get how much of menu is invisible
            var menuInvisibleSize = menuSize - menuWrapperSize;

// get how much have we scrolled to the left
            var getMenuPosition = function() {
                return $menu.scrollLeft();
            };

            var onScroll = function() {
                // get how much of menu is invisible
                menuInvisibleSize = menuSize - menuWrapperSize;
                // get how much have we scrolled so far
                var menuPosition = getMenuPosition();

                var menuEndOffset = menuInvisibleSize - paddleMargin;

                // show & hide the paddles
                // depending on scroll position
                if (menuPosition <= paddleMargin) {
                    $leftPaddle.addClass('hidden');
                    $rightPaddle.removeClass('hidden');
                } else if (menuPosition < menuEndOffset) {
                    // show both paddles in the middle
                    $leftPaddle.removeClass('hidden');
                    $rightPaddle.removeClass('hidden');
                } else if (menuPosition >= menuEndOffset) {
                    $leftPaddle.removeClass('hidden');
                    $rightPaddle.addClass('hidden');
                }
            };

            onScroll();
// finally, what happens when we are actually scrolling the menu
            $menu.on('scroll', onScroll);

// scroll to left
            $rightPaddle.on('click', function() {
                $menu.animate( { scrollLeft: menuInvisibleSize}, scrollDuration);
            });

// scroll to right
            $leftPaddle.on('click', function() {
                $menu.animate( { scrollLeft: '0' }, scrollDuration);
            });
        }
        // , watch: {
        //     '$store.state.ui.windowWidth': function (width) {
        //
        //     }
        // }
    };

    Vue.component('sv-comp-tabs', SvCompFormTabs);

    return SvCompFormTabs;
});