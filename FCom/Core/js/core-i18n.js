var FCom = {};

FCom.i18n = {
'Search' : 'Suche'
};

FCom._ = function(str) {
return FCom.i18n[str] || str;
}
