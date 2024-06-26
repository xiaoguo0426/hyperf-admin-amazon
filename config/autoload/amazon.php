<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
use function Hyperf\Support\env;

return [
    'report_template_path' => env('AMAZON_REPORT_TEMPLATE_PATH'),
    'report_lang' => [
        'MX' => [
            'Pedido' => 'Order',
            'Tarifa de servicio' => 'Service fee',
            'Trasferir' => 'Transfer',
            'Reembolso' => 'Refund',
            'Suscripción' => 'Subscription',
            'Ajuste' => 'Adjustment',
            'Deuda' => 'Debt',
        ],
        'DE' => [
            'Bestellung' => 'Order',
            'Servicegebühr' => 'Service fee',
            'Übertragung' => 'Transfer',
            'Erstattung' => 'Refund',
            'Abonnement' => 'Subscription',
            'Übertrag' => 'Transmission',
            'Gebührenerstattung' => 'Fee reimbursement',
            'Gebühr pro Einheit für Kundenrücksendung an Versand durch Amazon' => 'Fee per unit for customer return to shipping through Amazon',
            'Gebührenanpassung - Gewichts- und Dimensionsänderung' => 'Fee adjustment - weight and dimension change',
            'Versand durch Amazon Lagergebühr' => 'Shipping through Amazon warehousing fee',
            'An Konto mit der Endung:  001' => 'to the account ending in: 001',
            'FBA Customer Return Fee' => 'FBA Customer Return Fee',
            'Anpassung' => 'Adjustment',
            'Blitzangebotsgebühr' => 'Lightning offer fee',
        ],
        'FR' => [
            'Commande' => 'Order',
            'Frais de service' => 'Service fee',
            'Transfert' => 'Transfer',
            'Remboursement' => 'Refund',
            'Abonnement' => 'Subscription',
            'Solde négatif' => 'Negative balance',
            'Prix de la publicité' => 'Advertising prices',
            'vers le compte finissant en : 001' => 'to the account ending in: 001',
            'Frais de retour client FBA' => 'FBA Customer Return Fee',
            'Frais de stock Expédié par Amazon' => 'FBA Inventory Fees',
        ],
        'ES' => [
            'Pedido' => 'Order',
            'Tarifas de inventario de Logística de Amazon' => 'Amazon Logistics Fees',
            'Reembolso' => 'Refund',
            'FBA Customer Return Fee' => 'FBA Customer Return Fee',
            'Transferir' => 'Transfer',
        ],
        'IT' => [
            'Ordine' => 'Order',
            'Costo di stoccaggio Logistica di Amazon' => 'Amazon Logistics Security Cost',
            'Tariffa di stoccaggio Logistica di Amazon' => 'Amazon Logistics Return Rate',
            'Rimborso' => 'Refund',
            'FBA Customer Return Fee' => 'FBA Customer Return Fee',
            'Trasferimento' => 'Transfer',
        ],
    ],
];
