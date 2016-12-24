define(['sv-app', 'sv-comp-form', 'text!sv-page-sales-orders-form-nav-tpl', 'text!sv-page-sales-orders-form-info-tpl', 'text!sv-page-sales-orders-form-control-buttons-tpl', 'text!sv-page-sales-orders-form-general-info-tpl', 'text!sv-page-sales-orders-form-general-info-items-tpl', 'text!sv-page-sales-orders-form-general-info-main-info-tpl', 'text!sv-page-sales-orders-form-general-info-total-tpl', 
		'text!sv-page-sales-orders-form-comments-tpl', 
		'text!sv-page-sales-orders-form-comments-sort-bar-tpl', 
		'text!sv-page-sales-orders-form-comments-control-buttons-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-public-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-private-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-new-tpl'],
	   function (SvApp, SvCompForm, formNavTpl, formInfoTpl, SvCompControlButtons, SvCompOrderGeneralInfo, SvCompOrderGeneralInfoItems, SvCompOrderGeneralInfoMain, SvCompOrderGeneralInfoTotal, 
				  SvCompOrderComments, 
				  SvCompOrderCommentSortBar, 
				  SvCompOrderCommentControlButtons,
				  SvCompOrderCommentPublic, 
				  SvCompOrderCommentPrivate,
				  SvCompOrderCommentNew) {

    const FormNav = {
        template: formNavTpl
    };

	const FormInfo = {
		template: formInfoTpl
	};
	
	var ControlButtons = {
		template: SvCompControlButtons
	};
	
	var GeneralInfoItems = {
		template: SvCompOrderGeneralInfoItems
	};

	var GeneralInfoMain = {
		template: SvCompOrderGeneralInfoMain
	};

	var GeneralInfoTotal = {
		template: SvCompOrderGeneralInfoTotal
	};
	
	var GeneralInfo = {
		template: SvCompOrderGeneralInfo,
		components: {
			'sv-page-sales-orders-form-general-info-items': GeneralInfoItems,
			'sv-page-sales-orders-form-general-info-main-info': GeneralInfoMain,
			'sv-page-sales-orders-form-general-info-total': GeneralInfoTotal
		}
	};
	
	var CommentSortBar = {
		template: SvCompOrderCommentSortBar
	};
	
	var CommentControlButtons = {
		template: SvCompOrderCommentControlButtons
	};
	
	var CommentPublic = {
		template: SvCompOrderCommentPublic
	};

	var CommentPrivate = {
		template: SvCompOrderCommentPrivate
	};

	var CommentNew = {
		template: SvCompOrderCommentNew
	};

	var OrderComments = {
		template: SvCompOrderComments,
		components: {
			'sv-page-sales-orders-form-comments-sort-bar': CommentSortBar,
			'sv-page-sales-orders-form-comments-control-buttons': CommentControlButtons,
			'sv-page-sales-orders-form-comments-comment-public': CommentPublic,
			'sv-page-sales-orders-form-comments-comment-private': CommentPrivate,
			'sv-page-sales-orders-form-comments-comment-new': CommentNew
		}
	};
	
	return {
		store: SvApp.store,
        components: {
            'sv-comp-form': SvCompForm,
            'sv-page-sales-orders-form-nav': FormNav,
            'sv-page-sales-orders-form-info': FormInfo,
            'sv-page-sales-orders-form-control-buttons': ControlButtons,
            'sv-page-sales-orders-form-general-info': GeneralInfo,
			'sv-page-sales-orders-form-comments': OrderComments
		},
		mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: this.$router.currentRoute.fullPath,
                label: 'Edit Order #12345',
                breadcrumbs: [
                    {nav:'/sales', label:'Sales', icon_class:'fa fa-line-chart'},
                    {link:'/sales/orders', label:'Orders'}
                ]
            }});
		}
    };
});