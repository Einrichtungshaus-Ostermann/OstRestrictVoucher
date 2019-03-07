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

namespace OstRestrictVoucher\Setup;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Category\Category;

class Install
{
    /**
     * ...
     *
     * @var array
     */
    public static $attributes = [
        's_emarketing_vouchers_attributes' => [
            [
                'column' => 'ost_restrict_vouchers_articles',
                'type'   => 'multi_selection',
                'data'   => [
                    'label'            => 'Artikel ausschließen',
                    'helpText'         => 'Ausgeschlossene Artikel...',
                    'translatable'     => false,
                    'position'         => 200,
                    'displayInBackend' => true,
                    'custom'           => false,
                    'entity'           => Article::class,
                ]
            ],
            [
                'column' => 'ost_restrict_vouchers_suppliers',
                'type'   => 'multi_selection',
                'data'   => [
                    'label'            => 'Hersteller ausschließen',
                    'helpText'         => 'Ausgeschlossene Hersteller...',
                    'translatable'     => false,
                    'position'         => 210,
                    'displayInBackend' => true,
                    'custom'           => false,
                    'entity'           => Supplier::class,
                ]
            ],
            [
                'column' => 'ost_restrict_vouchers_categories',
                'type'   => 'multi_selection',
                'data'   => [
                    'label'            => 'Kategorien ausschließen',
                    'helpText'         => 'Ausgeschlossene Kategorien...',
                    'translatable'     => false,
                    'position'         => 220,
                    'displayInBackend' => true,
                    'custom'           => false,
                    'entity'           => Category::class,
                ]
            ],
        ]
    ];
    /**
     * Main bootstrap object.
     *
     * @var Plugin
     */
    protected $plugin;

    /**
     * ...
     *
     * @var InstallContext
     */
    protected $context;

    /**
     * ...
     *
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * ...
     *
     * @var CrudService
     */
    protected $crudService;

    /**
     * ...
     *
     * @param Plugin         $plugin
     * @param InstallContext $context
     * @param ModelManager   $modelManager
     * @param CrudService    $crudService
     */
    public function __construct(Plugin $plugin, InstallContext $context, ModelManager $modelManager, CrudService $crudService)
    {
        // set params
        $this->plugin = $plugin;
        $this->context = $context;
        $this->modelManager = $modelManager;
        $this->crudService = $crudService;
    }

    /**
     * ...
     *
     * @throws \Exception
     */
    public function install()
    {
    }
}
