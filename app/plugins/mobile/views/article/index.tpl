<{include file="../plugins/mobile/views/header.tpl"}>
<div class="sec nav">
<{if $canPost}>
	<a href="<{$mbase}>/article/<{$bName}>/post">����</a>|
<{/if}>
	<a href="<{$mbase}>/board/<{$bName}>">����</a>|
</div>
<ul class="list sec">
<li class="f">����:<{$title}></li>
<{foreach from=$info item=item key=k}>
<li<{cycle values=', class="hla"'}>>
	<div class="nav hl">
	<a class="plant"><{if $item.pos == 0}>¥��<{else}><{$item.pos}>¥<{/if}></a>|
	<a href="<{$mbase}>/user/query/<{$item.poster}>"><{$item.poster}></a>|
	<a class="plant"><{$item.time}></a>|
	<br />
	<a href="<{$mbase}>/article/<{$bName}>/post/<{$item.id}>">�ظ�</a>|
<{if $islogin}>
	<a href="<{$mbase}>/mail/send/<{$item.poster}>">����</a>|
<{if $item.subject}>
	<a href="<{$mbase}>/article/<{$bName}>/focus/<{$item.id}>">�Ƽ�</a>|
<{/if}>
<{/if}>
<{if $item.op}>
	<a href="<{$mbase}>/article/<{$bName}>/edit/<{$item.id}>">�༭</a>|
	<a href="<{$mbase}>/article/<{$bName}>/delete/<{$item.id}>">ɾ��</a>|
<{/if}>
	</div>
	<div class="sp"><{$item.content}></div>
</li>
<{/foreach}>
</ul>
<div class="sec nav">
<form action="<{$mbase}>/article/<{$bName}>/<{$reid}>" method="get">
<{if $curPage != 1}>
	<a href="<{$mbase}>/article/<{$bName}>/<{$gid}>?p=1">��ҳ</a>|
	<a href="<{$mbase}>/article/<{$bName}>/<{$gid}>?p=<{$curPage-1}>">��ҳ</a>|
<{/if}>
<{if $curPage != $totalPage}>
	<a href="<{$mbase}>/article/<{$bName}>/<{$gid}>?p=<{$curPage+1}>">��ҳ</a>|
	<a href="<{$mbase}>/article/<{$bName}>/<{$gid}>?p=<{$totalPage}>">βҳ</a>|
<{/if}>
	<a class="plant"><{$curPage}>/<{$totalPage}></a>|
	<a class="plant">ת��<input type="text" name="p" size="2" /><input type="submit" value="GO" class="btn" /></a>|
</form>
</div>
<{if $canPost}>
<div class="sec sp">
<form action="<{$mbase}>/article/<{$bName}>/post/<{$reid}>" method="post">
	<textarea name="content" style="width:100%" rows="1"></textarea><br />
	<input type="submit" class="btn" value="���ٻظ�" />
	<input type="hidden" name="subject" value="<{$reTitle}>" />
	<{if $anony}>
	<input type="hidden" name="anony" value="1" />
	<{/if}>
</form>
</div>
<{/if}>
<{include file="../plugins/mobile/views/footer.tpl"}>