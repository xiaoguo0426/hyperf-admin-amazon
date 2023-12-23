<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Util\Amazon\Report;

use App\Model\AmazonReportFbaStorageFeeChargesDataModel;
use App\Util\RedisHash\AmazonInventoryFnSkuToSkuMapHash;
use Hyperf\Database\Model\ModelNotFoundException;
use JsonException;
use RedisException;
use function Hyperf\Support\make;

class FbaStorageFeeChargesDataReport extends ReportBase
{
    /**
     * @throws \JsonException
     */
    public function run(string $report_id, string $file): bool
    {
        $config = $this->getHeaderMap();

        $merchant_id = $this->getMerchantId();
        $merchant_store_id = $this->getMerchantStoreId();

        $handle = fopen($file, 'rb');
        $header_line = str_replace("\r\n", '', fgets($handle));
        // 表头 需要处理换行符
        $headers = explode("\t", $header_line);

        $map = [];
        foreach ($headers as $index => $header) {
            if (! isset($config[$header])) {
                continue;
            }
            $map[$index] = $config[$header];
        }

        $data = [];
        $item_hash_map = [];
        while (! feof($handle)) {
            $row = explode("\t", str_replace("\r\n", '', fgets($handle)));

            $item = [];
            foreach ($map as $index => $value) {
                $val = $row[$index];
                if ($val === '--') {
                    $val = '0.00';
                } else if ($val === 'product_name') {
                    $val = preg_replace('/[^a-zA-Z0-9 ]/i', '', $val);
                }
                $item[$value] = $val;
            }

            $item['merchant_id'] = $merchant_id;
            $item['merchant_store_id'] = $merchant_store_id;

            ksort($item);
            $md5_hash = md5(json_encode($item, JSON_THROW_ON_ERROR));

            if (isset($data[$md5_hash])) {
                $idx = $md5_hash_idx_map[$md5_hash] ?? 1;
                $md5_hash = md5(json_encode($item, JSON_THROW_ON_ERROR) . $idx);
                $md5_hash_idx_map[$md5_hash] = $idx + 1;
            } else {
                $item['md5_hash'] = $md5_hash;
                $data[$md5_hash] = $item;
            }
        }
        fclose($handle);
        /**
         * @var AmazonInventoryFnSkuToSkuMapHash $hash
         */
        $hash = make(AmazonInventoryFnSkuToSkuMapHash::class, [$merchant_id, $merchant_store_id]);

        foreach ($data as $item) {

            $merchant_id = $item['merchant_id'];
            $merchant_store_id = $item['merchant_store_id'];
            $month_of_charge = $item['month_of_charge'];
            $asin = $item['asin'];
            $country_code = $item['country_code'];
            $fulfillment_center = $item['fulfillment_center'];//转运中心标识码。 因为同一个商品会被Amazon分在不同的仓库中，所以同一个SKU在每个仓库都有可能产生月度仓储费. 查询条件一定要加上
            $fn_sku = $item['fnsku'];
            $product_name = preg_replace('/[^a-zA-Z0-9 ]/i', '', $item['product_name']);

            $md5_hash = $item['md5_hash'];

            $seller_sku = '';
            try {
                $seller_sku = $hash->getSellerSkuByFnSku($fn_sku) ?? '';
            } catch (JsonException|RedisException $e) {
            }

            try {
                $collection = AmazonReportFbaStorageFeeChargesDataModel::query()
                    ->where('merchant_id', $merchant_id)
                    ->where('merchant_store_id', $merchant_store_id)
                    ->where('md5_hash', $md5_hash)
                    ->firstOrFail();
            } catch (ModelNotFoundException $modelNotFoundException) {
                $collection = new AmazonReportFbaStorageFeeChargesDataModel();
                $collection->merchant_id = $merchant_id;
                $collection->merchant_store_id = $merchant_store_id;
                $collection->month_of_charge = $month_of_charge;
                $collection->asin = $asin;
                $collection->seller_sku = $seller_sku;
                $collection->fnsku = $item['fnsku'];
                $collection->fulfillment_center = $fulfillment_center;
                $collection->country_code = $country_code;
            }

            $collection->product_name = $product_name;
            $collection->longest_side = $item['longest_side'];
            $collection->median_side = $item['median_side'];
            $collection->shortest_side = $item['shortest_side'];
            $collection->measurement_units = $item['measurement_units'];
            $collection->weight = $item['weight'];
            $collection->weight_units = $item['weight_units'];
            $collection->item_volume = $item['item_volume'];
            $collection->volume_units = $item['volume_units'];
            $collection->average_quantity_on_hand = $item['average_quantity_on_hand'];
            $collection->average_quantity_pending_removal = $item['average_quantity_pending_removal'];
            $collection->estimated_total_item_volume = $item['estimated_total_item_volume'];
            $collection->storage_utilization_ratio = $item['storage_utilization_ratio'] ?? '';
            $collection->storage_utilization_ratio_units = $item['storage_utilization_ratio_units'] ?? '';
            $collection->base_rate = $item['base_rate'] ?? '';
            $collection->utilization_surcharge_rate = $item['utilization_surcharge_rate'] ?? '';
            $collection->currency = $item['currency'];
            $collection->estimated_monthly_storage_fee = $item['estimated_monthly_storage_fee'];
            $collection->total_incentive_fee_amount = sprintf("%1.2f", $item['total_incentive_fee_amount']);
            $collection->breakdown_incentive_fee_amount = sprintf("%1.2f", $item['breakdown_incentive_fee_amount']);
            $collection->average_quantity_customer_orders = $item['average_quantity_customer_orders'];
            $collection->dangerous_goods_storage_type = $item['dangerous_goods_storage_type'];
            $collection->product_size_tier = $item['product_size_tier'];
            $collection->eligible_for_inventory_discount = $item['eligible_for_inventory_discount'];
            $collection->qualifies_for_inventory_discount = $item['qualifies_for_inventory_discount'];
            $collection->md5_hash = $md5_hash;

            $collection->save();
        }

        return true;
    }
}
