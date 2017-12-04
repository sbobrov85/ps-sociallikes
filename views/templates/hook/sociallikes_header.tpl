{*
 * Copyright (C) 2017 sbobrov85 <sbobrov85@gmail.com>.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *}

<meta property="og:type" content="product" />
<meta property="og:url" content="{$urls.current_url}" />
<meta property="og:title" content="{$page.meta.title}" />
<meta property="og:site_name" content="{$shop.name}" />
<meta property="og:description" content="{$page.meta.description}" />
{if isset($link_rewrite) && isset($cover) && isset($cover.id_image)}
<meta property="og:image" content="{$link->getImageLink($link_rewrite, $cover.id_image, large_default)}" />
{/if}
{if isset($pretax_price)}
<meta property="product:pretax_price:amount" content="{$product.price_tax_exc}" />
{/if}
<meta property="product:pretax_price:currency" content="{$currency.iso_code}" />
{if isset($price)}
<meta property="product:price:amount" content="{$product.price_amount}">
{/if}
<meta property="product:price:currency" content="{$currency.iso_code}">
{if isset($weight) && ($weight != 0)}
<meta property="product:weight:value" content="{$product.weight}">
<meta property="product:weight:units" content="{$product.weight_unit}">
{/if}
