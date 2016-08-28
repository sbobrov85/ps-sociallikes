{if $sociallikes }
<div class="social-likes__header-container">
    <span class="social-likes__header-content">
        {l s="Share with:"}
    </span>
</div>

<div class="social-likes">
    {foreach $sociallikes as $networkName => $networkProperties}
    <div
        class="{$networkName}"
    >
        {$networkProperties.networkLabel}
    </div>
    {/foreach}
</div>
{/if}