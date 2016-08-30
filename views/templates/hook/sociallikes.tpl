{if $sociallikes }

{if $properties.header}
<div class="social-likes__header-container">
    <span class="social-likes__header-content">
        {l s="Share with:"}
    </span>
</div>
{/if}

<div
    {($properties.block_classes) ? "class=\"`$properties.block_classes`\"" : ''}
    {($properties.id_attr) ? "id=\"`$properties.id_attr`\"" : ''}
    data-counters="{($properties.counters) ? 'yes' : 'no'}"
    data-zeroes="{($properties.zeroes) ? 'yes' : 'no'}"
    {($properties.layout == 'single') ? "data-single-title=\"`$properties.single_title`\"" : ''}
>
    {foreach $sociallikes as $networkName => $networkProperties}
    <div
        class="{$networkName}"
        {($networkProperties.title) ? "title=\"`$networkProperties.title`\"" : ''}
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