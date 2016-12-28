define(['sv-app', 'sv-comp-form', 'text!sv-page-sales-orders-form-nav-tpl', 'text!sv-page-sales-orders-form-info-tpl', 'text!sv-page-sales-orders-form-control-buttons-tpl', 'text!sv-page-sales-orders-form-general-info-tpl', 'text!sv-page-sales-orders-form-general-info-items-tpl', 'text!sv-page-sales-orders-form-general-info-main-info-tpl', 'text!sv-page-sales-orders-form-general-info-total-tpl', 
		'text!sv-page-sales-orders-form-comments-tpl', 
		'text!sv-page-sales-orders-form-comments-sort-bar-tpl', 
		'text!sv-page-sales-orders-form-comments-control-buttons-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-public-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-private-tpl', 
		'text!sv-page-sales-orders-form-comments-comment-new-tpl',
		'text!sv-page-sales-orders-form-details-tpl',
		'text!sv-page-sales-orders-form-details-information-tpl',
		'text!sv-page-sales-orders-form-details-payments-tpl',
		'text!sv-page-sales-orders-form-details-payments-sku-tpl'],
	   function (SvApp, SvCompForm, formNavTpl, formInfoTpl, SvCompControlButtons, SvCompOrderGeneralInfo, SvCompOrderGeneralInfoItems, SvCompOrderGeneralInfoMain, SvCompOrderGeneralInfoTotal, 
				  SvCompOrderComments, 
				  SvCompOrderCommentSortBar, 
				  SvCompOrderCommentControlButtons,
				  SvCompOrderCommentPublic, 
				  SvCompOrderCommentPrivate,
				  SvCompOrderCommentNew,
				  SvCompOrderDetails,
				  SvCompOrderDetailsInformation,
				  SvCompOrderDetailsPayments,
				  SvCompOrderDetailsPaymentsSku) {

    const FormNav = {
    	props: ['tab'],
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
		template: SvCompOrderGeneralInfoMain,
		data: function () {
			return {
				editing: {shipping: false, billing: false}
			}
		},
		methods: {
			toggleEditing: function(type) {

			}
		}
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
	
	var DetailsInformation = {
		template: SvCompOrderDetailsInformation
	};

	var DetailsPayments = {
		template: SvCompOrderDetailsPayments
	};
	
	var DetailsPaymentsSku = {
		template: SvCompOrderDetailsPaymentsSku
	};

	var OrderDetails = {
		template: SvCompOrderDetails,
		components: {
			'sv-page-sales-orders-form-details-information': DetailsInformation,
			'sv-page-sales-orders-form-details-payments': DetailsPayments,
			'sv-page-sales-orders-form-details-payments-sku': DetailsPaymentsSku
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
			'sv-page-sales-orders-form-comments': OrderComments,
			'sv-page-sales-orders-form-details': OrderDetails
		},
		data: function () {
			return {
				tab: 'main'
			}
		},
		methods: {
			switchTab: function (tab) {
				this.tab = tab;
			},
			buttonAction: function (act) {
				console.log(act);
			}
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