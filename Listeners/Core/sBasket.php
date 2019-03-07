<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Restrict Voucher
 *
 * @package   OstRestrictVoucher
 *
 * @author    Eike Brandt-Warneke <e.brandt-warneke@ostermann.de>
 * @copyright 2019 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstRestrictVoucher\Listeners\Core;

use Enlight_Components_Db_Adapter_Pdo_Mysql as Db;
use Enlight_Event_EventArgs as EventArgs;
use Enlight_Hook_HookArgs as HookArgs;
use sBasket as CoreClass;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;

class sBasket
{
    /**
     * The id for the current voucher.
     * We read it in an event before the execution of the hooks.
     *
     * @var int
     */
    public static $voucherId;
    /**
     * ...
     *
     * @var Db
     */
    private $db;

    /**
     * ...
     *
     * @var array
     */
    private $configuration;

    /**
     * ...
     *
     * @param Db    $db
     * @param array $configuration
     */
    public function __construct(Db $db, array $configuration)
    {
        // set params
        $this->db = $db;
        $this->configuration = $configuration;
    }

    /**
     * ...
     *
     * @param EventArgs $arguments
     */
    public function onAddVoucher(EventArgs $arguments)
    {
        // get the code
        $code = strtolower(stripslashes($arguments->get('code')));

        // get the voucher details id
        $id = (int) $this->db->fetchOne('
            SELECT id
            FROM s_emarketing_vouchers
            WHERE modus != 1
                AND LOWER(vouchercode) = ?
                AND (
                    (valid_to >= CURDATE() AND valid_from <= CURDATE())
                    OR valid_to IS NULL
                )
            ',
            [$code]
        );

        // not found?!
        if ($id === 0) {
            // try to find it via one-time code
            $id = (int) $this->db->fetchOne('
                SELECT voucherID
                FROM s_emarketing_voucher_codes c
                WHERE c.code = ?
                    AND c.cashed != 1
                LIMIT 1;',
                [$code]
            );
        }

        // save the id for the later hooks
        self::$voucherId = $id;
    }

    /**
     * ...
     *
     * @param HookArgs $arguments
     */
    public function reduceAmount(HookArgs $arguments)
    {
        /* @var $sBasket CoreClass */
        $sBasket = $arguments->getSubject();

        // current amount
        $amount = $arguments->getReturn();

        // even valid?!
        if ((float) $amount['totalAmount'] <= 0) {
            // nope
            return;
        }

        // get the id
        $id = (int) self::$voucherId;

        // cant be invalid...
        if ($id === 0) {
            // stop
            return;
        }

        /** @var DataLoader $loader */
        $loader = Shopware()->Container()->get('shopware_attribute.data_loader');

        // get the attributes
        $attributes = $loader->load('s_emarketing_vouchers_attributes', $id);

        // get everything
        $articles = (empty($attributes['ost_restrict_vouchers_articles']))
            ? ['000000']
            : array_map(function ($articleNumber) { return "'" . $articleNumber . "'"; }, explode('|', trim($attributes['ost_restrict_vouchers_articles'], '|')));

        $suppliers = (empty($attributes['ost_restrict_vouchers_suppliers']))
            ? [0]
            : explode('|', trim($attributes['ost_restrict_vouchers_suppliers'], '|'));

        $categories = (empty($attributes['ost_restrict_vouchers_categories']))
            ? [0]
            : explode('|', trim($attributes['ost_restrict_vouchers_categories'], '|'));

        // even valid?!
        if (empty($articles) && empty($suppliers) && empty($categories)) {
            // nothing to do
            return;
        }

        // ...
        $query = '
            SELECT basket.id, basket.articlename, (basket.quantity*(floor(basket.price * 100 + .55)/100)) AS amount
            FROM s_order_basket AS basket
                LEFT JOIN s_articles AS article
                    ON basket.articleID = article.id
                LEFT JOIN s_articles_categories_ro AS category
                    ON article.id = category.articleID AND category.categoryID IN (' . implode(',', $categories) . ')
            WHERE basket.modus = 0
                AND basket.sessionID = :sessionId
                AND ( 
                    ( basket.ordernumber IN (' . implode(',', $articles) . ') ) OR 
                    ( article.supplierID IN (' . implode(',', $suppliers) . ') ) OR
                    ( category.id IS NOT NULL )
                )
            GROUP BY basket.id
        ';
        $basket = $this->db->fetchAll($query, ['sessionId' => Shopware()->Session()->get('sessionId')]);

        // nothing to do?
        if (count($basket) === 0) {
            // all good
            return;
        }

        // amount to reduce...
        $reduce = 0;

        // ...
        foreach ($basket as $article) {
            // add it
            $reduce += (float) $article['amount'];
        }

        // not below 0...
        $reduce = ($reduce < 0) ? 0 : $reduce;

        // reduce the amount
        $amount['totalAmount'] = (float) $amount['totalAmount'] - $reduce;

        // and again at least 0
        $amount['totalAmount'] = ($amount['totalAmount'] < 0) ? 0 : $amount['totalAmount'];

        // ...
        $arguments->setReturn($amount);
    }
}
