<?php

class FCom_MultiLanguage_Admin extends BClass
{
	static public function bootstrap()
	{
		BRouting::i()
			->get('/translations', 'FCom_MultiLanguage_Admin_Controller_Translations.index')
			->any('/translations/.action', 'FCom_MultiLanguage_Admin_Controller_Translations')
		;

		BLayout::i()->addAllViews('Admin/views')
			->loadLayoutAfterTheme('Admin/layout.yml')
		;
	}
}