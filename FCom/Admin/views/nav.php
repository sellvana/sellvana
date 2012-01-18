<?
$adminHref = BApp::m('Denteva_Admin')->baseHref();
$mergeHref = BApp::m('Denteva_Merge')->baseHref();
$catalogHref = BApp::m('FCom_Catalog')->baseHref();
?>
<div style="background:#333; float:left;">
<?=$this->renderNodes() ?>
<!--
    <ul class="clearer" id="nav">
        <li><a href="<?=$mergeHref?>">Merge</a><ul>
            <li><a href="<?=$mergeHref?>/"><span>Import</span></a></li>
            <li><a href="<?=$mergeHref?>/manuf"><span>Manual Manufacturer Match</span></a></li>
            <li><a href="<?=$mergeHref?>/match"><span>Manual Product Match</span></a></li>
            <li><a href="<?=$mergeHref?>/category"><span>Manual Category Match</span></a></li>
        </ul></li>
        <li><a href="#">Catalog</a><ul>
            <li><a href="<?=$catalogHref?>/categories">Categories</a></li>
            <li><a href="<?=$catalogHref?>/attribute_sets">Attribute Sets</a></li>
            <li><a href="<?=$catalogHref?>/families">Product Families</a></li>
            <li><a href="<?=$catalogHref?>/products">Products</a></li>
        </ul></li>
        <li><a href="#">Manufacturers</a><ul>
            <li><a href="#">Manage</a></li>
        </ul></li>
        <li><a href="#">Accounts</a><ul>
            <li><a href="#">Companies</a></li>
            <li><a href="#">Locations</a></li>
            <li><a href="#">Uses</a></li>
        </ul></li>
        <li><a href="#">Reports</a><ul>
            <li><a href="#">Report 1</a></li>
            <li><a href="#">Report 2</a></li>
        </ul></li>
        <li><a href="#">System</a><ul>
            <li><a href="#">Tools</a></li>
            <li><a href="#">Administrators</a></li>
            <li><a href="#">Configuration</a></li>
        </ul></li>
    </ul>
-->
</div>