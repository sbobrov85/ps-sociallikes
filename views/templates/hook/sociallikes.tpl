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

{if $sociallikes }

{if $properties.header}
<div class="social-likes__header-container">
    <span class="social-likes__header-content">
        {$properties.header_title}:
    </span>
</div>
{/if}

<div
    {if $properties.block_classes}
        class="{{$properties.block_classes}}"
    {/if}
    {if $properties.id_attr}
        id="{{$properties.id_attr}}"
    {/if}
>
    {foreach $sociallikes as $networkName => $networkProperties}
    <div
        data-service="{$networkName}"
        {($networkProperties.title) ? "title=`$networkProperties.title`" : ''}
        {if $networkProperties.specific}
            {foreach $networkProperties.specific as $specificName => $specificValue}
                data-{$specificName}="{$specificValue}"
            {/foreach}
        {/if}
    >
        {if $properties.layout != 'notext'}
        {$networkProperties.text}
        {/if}
    </div>
    {/foreach}
</div>
{/if}
