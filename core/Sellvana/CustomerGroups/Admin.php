<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerGroups_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_CustomerGroups_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'customer_groups' => "Customer Groups"
        ]);
    }

    public function onProductAfterSave($args)
    {
        $prod = $args['model'];

        if ($prod->get('price_info')) {
            $data = $this->BUtil->fromJson($prod->get('price_info'));
            $rows = $data['rows'];
            $remove_ids = $data['remove_ids'];

            $model = $this->Sellvana_Catalog_Model_ProductPrice;

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
