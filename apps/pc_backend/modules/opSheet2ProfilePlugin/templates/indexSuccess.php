<?php use_javascript('http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.6.2.min.js') ?>

<h2>プラグイン設定</h2>
<form action="<?php echo url_for('opSheet2ProfilePlugin/index') ?>" method="post">
<table>
<?php echo $form ?>
<tr>
<td colspan="2"><input type="submit" value="<?php echo __('設定変更') ?>" /></td>
</tr>
</table>
</form>

<div id="click">CLICK!</div>

<script>
$(function(){
  $("div#click").click(function(){
    alert("CLICK");
  });
});

</script>
