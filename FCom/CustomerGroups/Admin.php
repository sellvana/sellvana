<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission([
            'customer_groups' => "Customer Groups"
        ]);
    }

    public static function onProductAfterSave($args)
    {
        $prod = $args['model'];

        if ($prod->get('price_info')) {
            $data = BUtil::fromJson($prod->get('price_info'));
            $rows = $data['rows'];
            $remove_ids = $data['remove_ids'];

            $model = FCom_CustomerGroups_Model_TierPrice::i();

            foreach ($remove_ids as $id) {
                $r = $model->load($id);
                if (!empty($r))
                    $r->delete();
            }

            foreach ($rows as $row) {
                if (isset($row['_new'])) {
                    unset($row['_new']);
                    unset($row['id']);
                    $row['product_id'] = $prod->id;
                    /**
                     * onProductAfterSave called multiple times when product save
                     * @Todo: find other solutions
                     */
                    $tier = $model->orm()->where('product_id', $row['product_id'])
                        ->where('group_id', $row['group_id'])->where('qty', $row['qty'])->find_one();
                    if (!$tier) {
                        $model->create($row)->save();
                    }
                } else {
                    $model->load($row['id'])->set($row)->save();
                }
            }


        }
    }
}
