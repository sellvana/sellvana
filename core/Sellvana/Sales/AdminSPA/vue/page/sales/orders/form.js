define(['sv-app', 'sv-comp-form', 'text!sv-page-sales-orders-form-nav-tpl', 'text!sv-page-sales-orders-form-info-tpl', 'text!sv-page-sales-orders-form-control-buttons-tpl', 'text!sv-page-sales-orders-form-general-info-tpl', 'text!sv-page-sales-orders-form-general-info-items-tpl', 'text!sv-page-sales-orders-form-general-info-main-info-tpl', 'text!sv-page-sales-orders-form-general-info-total-tpl'],
	   function (SvApp, SvCompForm, formNavTpl, formInfoTpl, SvCompControlButtons, SvCompOrderGeneralInfo, SvCompOrderGeneralInfoItems, SvCompOrderGeneralInfoMain, SvCompOrderGeneralInfoTotal) {

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
			'sv-page-sales-orders-form-general-info-total': GeneralInfoTotal,
		}
	};
	
	

    return {
        components: {
            'sv-comp-form': SvCompForm,
            'sv-page-sales-orders-form-nav': FormNav,
            'sv-page-sales-orders-form-info': FormInfo,
            'sv-page-sales-orders-form-control-buttons': ControlButtons,
            'sv-page-sales-orders-form-general-info': GeneralInfo,
			
        }
    };
});