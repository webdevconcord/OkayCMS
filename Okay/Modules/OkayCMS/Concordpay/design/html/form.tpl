<form method="post" action="https://pay.concord.ua/api/" accept-charset="utf-8">
<input type="hidden" name="operation"    value="{$operation|escape}">
<input type="hidden" name="merchant_id"  value="{$merchant_id|escape}">
<input type="hidden" name="amount"       value="{$amount|escape}">
<input type="hidden" name="signature"    value="{$signature|escape}">
<input type="hidden" name="order_id"     value="{$order_id|escape}">
<input type="hidden" name="currency_iso" value="{$currency_iso|escape}">
<input type="hidden" name="description"  value="{$description|escape}">
{if is_array($add_params)}
  {foreach from=$add_params key=param_key item=add_param}
    <input type="hidden" name="add_params[{$param_key}]" value="{$add_param|escape}">
  {/foreach}
{/if}
<input type="hidden" name="approve_url"       value="{$approve_url|escape}">
<input type="hidden" name="decline_url"       value="{$decline_url|escape}">
<input type="hidden" name="cancel_url"        value="{$cancel_url|escape}">
<input type="hidden" name="callback_url"      value="{$callback_url|escape}">
<input type="hidden" name="language"          value="{$language|escape}">
<input type="hidden" name="client_first_name" value="{$client_first_name|escape}">
<input type="hidden" name="client_last_name"  value="{$client_last_name|escape}">
<input type="hidden" name="email"             value="{$email|escape}">
<input type="hidden" name="phone"             value="{$phone|escape}">
<input type="submit" class="button"           value="{$lang->form_to_pay}">
</form>