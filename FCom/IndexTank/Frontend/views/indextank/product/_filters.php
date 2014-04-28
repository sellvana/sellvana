<?php
$s = $this->state;
?>
<div class="panel panel-filter">
    <div class="panel-heading">
     <span class="panel-title">Narrow Results</span>
  </div>
  <form method="get" action="">
      <?=$this->view( 'indextank/product/_pager_categories' )->set( 's', $s )?>
  <?php if ( !empty( $s[ 'available_facets' ] ) ): ?>
      <?php foreach ( $s[ 'available_facets' ] as $label => $data ):?>
          <div class="subpanel panel-attribute">
            <div class="subpanel-heading">
              <span class="subpanel-title"><?=$label?>
            </div>
            <ul>
            <?php foreach ( $data as $obj ): ?>
                <?php if ( !empty( $s[ 'filter_selected' ][ $obj->key ] ) && in_array( $obj->name, $s[ 'filter_selected' ][ $obj->key ] ) ):?>
                    <li><a class="active" href="<?=BUtil::setUrlQuery( BRequest::currentUrl(), [ $obj->param => '' ] )?>"><span class="icon"></span><?=$obj->name?> <span class="badge">(<?=$obj->count?>)</span></a></li>
                    <?php if ( true == $s[ 'save_filter' ] ):?>
                        <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                    <?php endif; ?>
                <?php else :?>
                    <li><a href="<?=BUtil::setUrlQuery( BRequest::currentUrl(), [ $obj->param => $obj->name ] )?>"><span class="icon"></span><?=$obj->name?> <span class="badge">(<?=$obj->count?>)</span></a></li>
                <?php endif; ?>
            <?php endforeach ?>
            </ul>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>
  </form>
</div>

