{extends file='frontend/checkout/confirm.tpl'}

{block name='frontend_index_content_top'}

{if $BarzahlenPaymentError}
<div class="grid_20 first">
  {* Step box *}
  {include file="frontend/register/steps.tpl" sStepActive="finished"}

  <div class="error agb_confirm">
    <div class="center">
      <strong>
        {$BarzahlenPaymentError}
      </strong>
    </div>
  </div>
</div>
{/if}

{/block}