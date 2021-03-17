<form method="post" action="https://pay.concord.ua/api/" accept-charset="utf-8">
    <input type="hidden" name="operation"    value="{$operation|escape}">
    <input type="hidden" name="merchant_id"  value="{$merchant_id|escape}">
    <input type="hidden" name="amount"       value="{$amount|escape}">
    <input type="hidden" name="signature"    value="{$signature|escape}">
    <input type="hidden" name="order_id"     value="{$order_id|escape}">
    <input type="hidden" name="currency_iso" value="{$currency_iso|escape}">
    <input type="hidden" name="description" value="{$description|escape}">
    <input type="hidden" name="add_params" value="{$add_params|escape}">

    <input type="hidden" name="approve_url" value="{$approve_url|escape}">
    <input type="hidden" name="decline_url" value="{$decline_url|escape}">
    <input type="hidden" name="cancel_url" value="{$cancel_url|escape}">
    <input type="hidden" name="callback_url" value="{$callback_url|escape}">

    <input type="submit" class="button" value="{$lang->form_to_pay}">
</form>