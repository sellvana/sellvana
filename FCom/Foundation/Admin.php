<?php

class FCom_Foundation_Admin extends BClass
{
	static public function bootstrap()
	{
		BLayout::i()->addAllViews('Admin/views')->loadLayoutAfterTheme('Admin/layout.yml');
	}
}