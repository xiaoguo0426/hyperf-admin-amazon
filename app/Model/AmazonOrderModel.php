<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $merchant_id 商户id
 * @property int $merchant_store_id 店铺id
 * @property string $region 地区
 * @property string $amazon_order_id 亚马逊定义的订单标识符，格式为3-7-7
 * @property string $seller_order_id 卖家定义的订单标识符
 * @property string $purchase_date 订单创建时间
 * @property string $last_update_date 上次更新订单的日期
 * @property string $order_status 订单状态 Pending,Unshipped,PartiallyShipped,Shipped,Canceled,Unfulfillable,InvoiceUnconfirmed,PendingAvailability
 * @property string $fulfillment_channel 订单是由亚马逊（AFN）还是由卖方（MFN）完成     MFN,AFN
 * @property string $sales_channel 订单中第一项的销售渠道
 * @property string $order_channel 订单中第一项的订单通道
 * @property string $ship_service_level 订单的发货服务级别
 * @property string $order_total_currency 订单的总费用(货币)
 * @property string $order_total_amount 订单的总费用
 * @property int $number_of_items_shipped 装运的项目数
 * @property int $number_of_items_unshipped 未装运的项目数
 * @property string $payment_execution_detail 关于货到付款（COD）订单的子付款方式的信息
 * @property string $payment_method 订单的付款方式。COD,CVS,Other   此属性仅限于货到付款（COD）和便利店（CVS）付款方式。除非您需要PaymentExecutionDetailItem对象提供的特定COD付款信息，否则建议使用PaymentMethodDetails属性获取付款方式信息。
 * @property string $payment_method_details 订单的付款方式列表
 * @property string $marketplace_id 下订单的市场的标识符
 * @property string $shipment_service_level_category 订单的装运服务级别类别 Expedited, FreeEconomy, NextDay, SameDay, SecondDay, Scheduled, Standard.
 * @property string $easy_ship_shipment_status Amazon Easy Ship订单的状态。此属性仅适用于Amazon Easy Ship订单。
 * @property string $cba_displayable_shipping_label 亚马逊（CBA）结账的定制发货标签
 * @property string $order_type 订单类型 StandardOrder,LongLeadTimeOrder,Preorder,BackOrder,SourcingOnDemandOrder
 * @property string $earliest_ship_date 您承诺发货订单的时间段的开始。采用ISO 8601日期时间格式。仅针对卖方完成的订单退回。
 * @property string $latest_ship_date 您承诺发货订单的时间段结束
 * @property string $earliest_delivery_date 您承诺履行订单的时间段的开始。采用ISO 8601日期时间格式。仅针对卖方完成的订单退回。
 * @property string $latest_delivery_date 您承诺履行订单的期限结束。采用ISO 8601日期时间格式。仅针对卖家完成的订单返回，这些订单没有挂起可用性、挂起或取消状态
 * @property string $is_business_order 如果为true，则订单为Amazon Business订单。亚马逊商业订单是指买方是经验证的商业买家的订单
 * @property string $is_prime 如果为true，则订单是卖家完成的亚马逊Prime订单。
 * @property string $is_premium_order 如果为true，则订单具有“高级配送服务级别协议”。有关高级配送订单的更多信息，请参阅您所在市场的卖家中心帮助中的“高级配送选项”
 * @property string $is_global_express_enabled 如果为true，则订单为GlobalExpress订单
 * @property string $replaced_order_id 正在替换的订单的订单ID值。仅当IsReplacementOrder=true时返回。
 * @property string $is_replacement_order 如果为true，则这是替换订单。
 * @property string $promise_response_due_date 表示卖方必须以预计发货日期回复买方的日期。仅针对按需采购订单退回。
 * @property string $is_estimated_ship_date_set 如果为true，则为订单设置预计发货日期。仅针对按需采购订单退回
 * @property string $is_sold_by_ab 如果为true，则此订单中的商品由Amazon Business EU SARL（ABEU）购买并转售。通过购买并立即转售您的物品，ABEU成为记录的卖家，使您的库存可供不从第三方卖家购买的客户出售。
 * @property string $is_iba 如果为true，则此订单中的商品由Amazon Business EU SARL（ABEU）购买并转售。通过购买并立即转售您的物品，ABEU成为记录的卖家，使您的库存可供不从第三方卖家购买的客户出售。
 * @property string $default_ship_from_location_address 卖方装运物品的推荐地点。结账时计算。卖方可以选择或不选择从该地点发货
 * @property string $buyer_invoice_preference 买方的发票偏好。仅在TR市场上可用
 * @property string $buyer_tax_information 包含业务发票税务信息
 * @property string $fulfillment_instruction 包含有关履行的说明，如从何处履行
 * @property string $is_ispu 如果为true，则此订单标记为从商店提货，而不是交付
 * @property string $is_access_point_order 如果为true，则将此订单标记为要交付给接入点。访问位置由客户选择。接入点包括亚马逊中心储物柜、亚马逊中心柜台和运营商运营的取货点。
 * @property string $marketplace_tax_info 有关市场的税务信息
 * @property string $seller_display_name 卖家在市场上注册的友好名称
 * @property string $shipping_address 订单的发货地址
 * @property string $buyer_email 买家email
 * @property string $buyer_info 买家信息
 * @property string $automated_shipping_settings 包含有关配送设置自动程序的信息，例如订单的配送设置是否自动生成，以及这些设置是什么
 * @property string $has_regulated_items 订单是否包含在履行之前可能需要额外批准步骤的监管项目
 * @property string $electronic_invoice_status 电子发票的状态 NotRequired,NotFound,Processing,Errored,Accepted
 * @property int $is_vine_order 是否为VINE类型订单
 * @property Carbon $created_at 订单拉取入库时间(内部使用)
 * @property Carbon $updated_at 订单最后一次更新入库时间(内部使用)
 */
class AmazonOrderModel extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'amazon_order';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'int', 'merchant_id' => 'integer', 'merchant_store_id' => 'integer', 'number_of_items_shipped' => 'integer', 'number_of_items_unshipped' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
