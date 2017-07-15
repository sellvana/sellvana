define(['vue', 'vuex', 'sv-app-data'], function (Vue, Vuex, SvAppData) {
    Vue.use(Vuex);

    var store = new Vuex.Store({
        strict: true,
        state: {
            env: SvAppData.env,
            user: SvAppData.user,
            navTree: SvAppData.nav_tree,
            csrfToken: SvAppData.csrf_token,
            favorites: SvAppData.favorites,
            messages: [
                // {type: 'error', text: 'Error message'},
                // {type: 'warning', text: 'Warning message'},
                // {type: 'success', text: 'Success message'},
                // {type: 'info', text: 'Info message'}
            ],
            curPage: {
                link: '/',
                label: '',
                icon_class: '',
                breadcrumbs: [
                ]
            }
        },
        mutations: {
            setData: function (state, data) {
                for (key in data) {
                    Vue.set(state, key, data[key]);
                }
            },
            personalizeGridColumn: function (state, data) {
                var grid = data.grid, col = data.col;
                if (!state.personalize) {
                    Vue.set(state, 'personalize', {});
                }
                if (!state.personalize.grid) {
                    Vue.set(state.personalize, 'grid', {});
                }
                if (!state.personalize.grid[grid.config.id]) {
                    Vue.set(state.personalize.grid, grid.config.id, {});
                }
                if (!state.personalize.grid[grid.config.id].columns) {
                    Vue.set(state.personalize.grid[grid.config.id], 'columns', {});
                }
                if (!state.personalize.grid[grid.config.id].columns[col.field]) {
                    Vue.set(state.personalize.grid[grid.config.id].columns, col.field, 1);
                } else {
                    Vue.set(state.personalize.grid[grid.config.id].columns, col.field, 0);
                }
            },
            setGridConfigColumns: function (state, grid) {
                if (!state.grid) {
                    Vue.set(state, 'grid', {});
                }
                if (!state.grid[grid.config.id]) {
                    Vue.set(state.grid, grid.config.id, {});
                }
                if (!state.grid[grid.config.id].config) {
                    Vue.set(state.grid[grid.config.id], 'config', {});
                }
                Vue.set(state.grid[grid.config.id].config, 'columns', grid.config.columns);
            },
            addFavorite: function (state, fav) {
                var favs = state.favorites || [];
                for (var i = 0; i < favs.length; i++) {
                    if (favs[i].link === fav.link) {
                        return;
                    }
                }
                favs.push(fav);
                Vue.set(state, 'favorites', favs);
            },
            removeFavorite: function (state, fav) {
                var favs = state.favorites;
                for (var i = 0; i < favs.length; i++) {
                    if (favs[i].link === fav.link) {
                        favs.splice(i, 1);
                        break;
                    }
                }
                Vue.set(state, 'favorites', favs);
            },
            logout: function (state) {
                Vue.set(state, 'user', {});
            },
            addMessage: function (state, message) {
                state.messages.push(message);
            },
            removeMessage: function (state, message) {
                for (var i = 0, l = state.messages.length; i < l; i++) {
                    if (_.isEqual(state.messages[i], message)) {
                        state.messages.splice(i, 1);
                        break;
                    }
                }
            }
        }
    });

    store.registerModule('ui', {
        //namespaced: true,
        state: {
            ddCurrent: false,
            mainNavOpen: true,
            overlayActive: false,
            windowWidth: null,
            pageClickCounter: 0
        },
        mutations: {
            ddToggle: function (state, ddName) {
                state.ddCurrent = state.ddCurrent === ddName ? false : ddName;
            },
            mainNavToggle: function (state) {
                state.mainNavOpen = !state.mainNavOpen;
            },
            pageClick: function (state) {
                state.pageClickCounter++;
            },
            windowResize: function (state, width) {
                state.windowWidth = width;
                state.mainNavOpen = width > 1024;
            },
            overlay: function (state, active) {
                state.overlayActive = active;
            }
        }
    });

    return store;
});