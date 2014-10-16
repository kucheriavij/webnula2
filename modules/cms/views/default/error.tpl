{extends "cms@layouts.base"}

{block content}
	<div class="alert alert-danger" role="alert">
		{if $code === 500}
			[{$code}] {$message}
			<pre>
				{$trace}
			</pre>
		{else}
			[{$code}] {$message}
		{/if}
	</div>
{/block}