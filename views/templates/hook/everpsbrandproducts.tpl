{*
* Project : everpsbrandproducts
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}
{if isset($evermanufacturer_products) && $evermanufacturer_products}
<section class="featured-products clearfix mt-3">
    <h2 class="text-center">
        <a href="{$link->getManufacturerLink($evermanufacturer)}" title="{$evermanufacturer->name|escape:'htmlall':'UTF-8'}">
        {l s='Products with same brand:' d='Modules.everpsbrandproducts.Shop'} {$evermanufacturer->name|escape:'htmlall':'UTF-8'}
        </a>
    </h2>
    <div class="products row">
    {foreach from=$evermanufacturer_products item="product"}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product}
    {/foreach}
    </div>
</section>
{/if}