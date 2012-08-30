<header class="header-wrapper">
	<div class="header-top-wrapper">
	    <div class="header-top">
	        <a href="#" class="site-logo">Fulleron</a>
	        <?=$this->view('cart/header')?>
	        <nav class="sup-links">
	            <ul>
	                <?php if(FCom_Customer_Model_Customer::isLoggedIn()):?>
						<li class="header-sup-signin">
	                        Hello <?=FCom_Customer_Model_Customer::sessionUser()->email?>
	                        <a href="<?=BApp::href('logout')?>">Logout</a>
	                    </li>
	                    <li><a href="<?=BApp::href('customer/myaccount')?>">My Account</a></li>
	                <?php else: ?>
						<li class="header-sup-signin">Hello there!</li>
						<li><a href="<?=BApp::href('login')?>">Sign in</a></li>
						<li class="header-sup-wishlist"><a href="<?=BApp::href('customer/register')?>">Register</a></li>
	                <?php endif; ?>
					<li><a href="<?=BApp::href('wishlist')?>">Wishlist</a></li>
	            </ul>
	        </nav>
	    </div>
	</div>
	<div class="header-nav-wrapper">
	    <div class="header-nav">
            <nav class="site-nav">
                <ul>
                    <li><a href="<?=BApp::baseUrl()?>">Home</a>
                    <?=$this->view('nav')?>
                </ul>
            </nav>
            <form class="site-search">
            	<fieldset>
            		<input type="text" placeholder="Search entire store"/><button class="icon-button"><span>Search</span></button>
            	</fieldset>
            </form>
	    </div>
	</div>
</header>